<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
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
        $this->authorize('manageMembers', $team);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        $email = strtolower($request->input('email'));

        // Check if user is already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $team->hasMember($existingUser)) {
            return back()->withErrors(['email' => 'This user is already a member of the team.']);
        }

        // Check if there's already a pending invitation for this email
        if ($team->pendingInvitations()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'An invitation has already been sent to this email address.']);
        }

        // Check seat limits
        if (! $team->canAddMember()) {
            $maxSeats = $team->getMaxSeats();

            return back()->withErrors([
                'email' => "Your team has reached the maximum of {$maxSeats} member(s). Please upgrade your plan to invite more members.",
            ]);
        }

        // Create the invitation
        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => $request->input('role'),
            'invited_by' => auth()->id(),
        ]);

        // Send invitation email
        Notification::route('mail', $email)
            ->notify(new TeamInvitationNotification($invitation));

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
                'email' => $invitation->email,
                'role' => $invitation->role,
                'inviter_name' => $invitation->inviter->name,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
            'hasAccount' => User::where('email', $invitation->email)->exists(),
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

        // Check if user is already a member
        if ($invitation->team->hasMember($user)) {
            $invitation->markAsAccepted();

            return redirect()->route('teams.show', $invitation->team)
                ->with('info', 'You are already a member of this team.');
        }

        // Check seat limits (in case they changed since invitation was sent)
        if (! $invitation->team->canAddMember()) {
            return back()->withErrors([
                'error' => 'This team has reached its member limit. Please contact the team owner.',
            ]);
        }

        // Add user to team
        $invitation->team->members()->attach($user->id, [
            'role' => $invitation->role,
        ]);

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        return redirect()->route('teams.show', $invitation->team)
            ->with('success', "You've joined {$invitation->team->name}!");
    }
}
