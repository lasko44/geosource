<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    /**
     * Send a new team invitation.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('inviteMembers', $team);

        // Block invitations when team is over seat limit due to subscription downgrade
        if ($team->isOverSeatLimit()) {
            return back()->withErrors([
                'email' => 'Your team is over its seat limit. Please remove members or upgrade your subscription before sending new invitations.',
            ]);
        }

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        // Only owners can invite admins
        if ($request->input('role') === 'admin' && ! $team->isOwner(auth()->user())) {
            return back()->withErrors([
                'role' => 'Only team owners can invite administrators.',
            ]);
        }

        $email = strtolower($request->input('email'));

        // Check if user is already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $team->hasMember($existingUser)) {
            return back()->withErrors(['email' => 'This user is already a member of the team.']);
        }

        // Check if there's already a pending invitation for this email
        $existingInvitation = $team->invitations()->where('email', $email)->first();
        if ($existingInvitation) {
            if ($existingInvitation->isPending()) {
                return back()->withErrors(['email' => 'An invitation has already been sent to this email address.']);
            }
            // Delete old expired or accepted invitations to allow re-inviting
            $existingInvitation->delete();
        }

        // Use transaction with locking to prevent race condition on seat limits
        $result = DB::transaction(function () use ($team, $email, $request) {
            // Lock the team row to prevent concurrent seat checks
            $lockedTeam = Team::where('id', $team->id)->lockForUpdate()->first();

            // Check seat limits with lock held
            if (! $lockedTeam->canAddMember()) {
                return [
                    'error' => true,
                    'maxSeats' => $lockedTeam->getMaxSeats(),
                ];
            }

            // Create the invitation while lock is held
            $invitation = $lockedTeam->invitations()->create([
                'email' => $email,
                'role' => $request->input('role'),
                'invited_by' => auth()->id(),
            ]);

            return [
                'error' => false,
                'invitation' => $invitation,
            ];
        });

        if ($result['error']) {
            return back()->withErrors([
                'email' => "Your team has reached the maximum of {$result['maxSeats']} member(s). Please upgrade your plan to invite more members.",
            ]);
        }

        // Send invitation email (outside transaction to not block)
        Notification::route('mail', $email)
            ->notify(new TeamInvitationNotification($result['invitation']));

        return redirect()->route('teams.members', $team)
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Cancel a pending invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation)
    {
        $this->authorize('manageMembers', $team);

        if ($invitation->team_id !== $team->id) {
            abort(404);
        }

        $invitation->delete();

        return redirect()->route('teams.members', $team)
            ->with('success', 'Invitation cancelled.');
    }

    /**
     * Resend an invitation.
     */
    public function resend(Team $team, TeamInvitation $invitation)
    {
        $this->authorize('manageMembers', $team);

        if ($invitation->team_id !== $team->id) {
            abort(404);
        }

        if ($invitation->isAccepted()) {
            return back()->withErrors(['error' => 'This invitation has already been accepted.']);
        }

        // Reset expiration and regenerate token
        $invitation->update([
            'token' => \Illuminate\Support\Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // Resend the email
        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return redirect()->route('teams.members', $team)
            ->with('success', 'Invitation resent successfully!');
    }

    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token)
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation) {
            return Inertia::render('teams/InvitationExpired', [
                'message' => 'This invitation link is invalid or has expired.',
            ]);
        }

        if ($invitation->isAccepted()) {
            return Inertia::render('teams/InvitationExpired', [
                'message' => 'This invitation has already been accepted.',
            ]);
        }

        if ($invitation->isExpired()) {
            return Inertia::render('teams/InvitationExpired', [
                'message' => 'This invitation has expired. Please ask the team admin to send a new invitation.',
            ]);
        }

        return Inertia::render('teams/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'team_name' => $invitation->team->name,
                'role' => $invitation->role,
                'inviter_name' => $invitation->inviter->name,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            return redirect()->route('login')
                ->withErrors(['error' => 'This invitation is no longer valid.']);
        }

        $user = auth()->user();

        // Ensure the logged-in user's email matches the invitation
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return back()->withErrors([
                'error' => "This invitation was sent to {$invitation->email}. Please log in with that email address to accept.",
            ]);
        }

        // Use database transaction with locking to prevent race conditions
        try {
            return DB::transaction(function () use ($invitation, $user) {
                // Lock the team row to prevent concurrent seat modifications
                $team = Team::where('id', $invitation->team_id)->lockForUpdate()->first();

                // Re-check invitation status within the lock
                $lockedInvitation = TeamInvitation::where('id', $invitation->id)->lockForUpdate()->first();
                if ($lockedInvitation->isAccepted()) {
                    return redirect()->route('teams.show', $team)
                        ->with('info', 'This invitation has already been accepted.');
                }

                // Check if user is already a member
                if ($team->hasMember($user)) {
                    $lockedInvitation->markAsAccepted();

                    return redirect()->route('teams.show', $team)
                        ->with('info', 'You are already a member of this team.');
                }

                // Check seat limits (in case they changed since invitation was sent)
                if (! $team->canAddMember()) {
                    return back()->withErrors([
                        'error' => 'This team has reached its member limit. Please contact the team owner.',
                    ]);
                }

                // Add user to team
                $team->members()->attach($user->id, [
                    'role' => $lockedInvitation->role,
                ]);

                // Mark invitation as accepted
                $lockedInvitation->markAsAccepted();

                return redirect()->route('teams.show', $team)
                    ->with('success', "You've joined {$team->name}!");
            });
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'An error occurred while accepting the invitation. Please try again.',
            ]);
        }
    }
}
