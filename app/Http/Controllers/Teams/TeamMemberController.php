<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    /**
     * Show the team members.
     */
    public function index(Team $team): Response
    {
        $this->authorize('view', $team);

        $user = auth()->user();
        $isAdmin = $team->isAdmin($user);

        // Only admins can see full emails and manage invitations
        return Inertia::render('teams/Members', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'owner' => [
                'id' => $team->owner->id,
                'name' => $team->owner->name,
            ],
            'members' => $team->members()->where('user_id', '!=', $team->owner_id)->get()->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $isAdmin ? $member->email : $this->maskEmail($member->email),
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->created_at->toISOString(),
            ]),
            // Only show pending invitations to admins who can manage them
            'pendingInvitations' => $isAdmin
                ? $team->pendingInvitations()->with('inviter')->get()->map(fn ($invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'inviter_name' => $invitation->inviter->name,
                    'expires_at' => $invitation->expires_at->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                ])
                : [],
            'seats' => [
                'used' => $team->getUsedSeats(),
                'max' => $team->getMaxSeats(),
                'available' => $team->getAvailableSeats(),
                'can_add' => $team->canAddMember(),
                'has_collaboration' => $team->hasCollaborationEnabled(),
                'is_over_limit' => $team->isOverSeatLimit(),
                'over_limit_count' => $team->getSeatsOverLimit(),
            ],
            'userRole' => $team->getUserRole($user),
            'isOwner' => $team->isOwner($user),
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Mask an email address for privacy.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        // Show first 2 chars of local part, mask the rest
        $maskedLocal = strlen($local) > 2
            ? substr($local, 0, 2).str_repeat('*', min(strlen($local) - 2, 5))
            : $local;

        // Mask domain but show TLD
        $domainParts = explode('.', $domain);
        $tld = array_pop($domainParts);
        $maskedDomain = '***.'.$tld;

        return $maskedLocal.'@'.$maskedDomain;
    }

    /**
     * Add a new member to the team.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('inviteMembers', $team);

        // Block adding members when team is over seat limit due to subscription downgrade
        if ($team->isOverSeatLimit()) {
            return back()->withErrors([
                'email' => 'Your team is over its seat limit. Please remove members or upgrade your subscription before adding new members.',
            ]);
        }

        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        // Only owners can add admins
        if ($request->input('role') === 'admin' && ! $team->isOwner(auth()->user())) {
            return back()->withErrors([
                'role' => 'Only team owners can add administrators.',
            ]);
        }

        $user = User::where('email', $request->input('email'))->first();

        if ($team->hasMember($user)) {
            return back()->withErrors(['email' => 'This user is already a member of the team.']);
        }

        // Check seat limits before adding member
        if (! $team->canAddMember()) {
            $maxSeats = $team->getMaxSeats();

            return back()->withErrors([
                'email' => "Your team has reached the maximum of {$maxSeats} member(s). Please upgrade your plan to add more members.",
            ]);
        }

        $team->members()->attach($user->id, ['role' => $request->input('role')]);

        return redirect()->route('teams.members', $team)
            ->with('success', 'Member added successfully!');
    }

    /**
     * Update a member's role.
     */
    public function update(Request $request, Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

        if ($team->isOwner($user)) {
            return back()->withErrors(['error' => 'Cannot change the role of the team owner.']);
        }

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        $currentUser = auth()->user();
        $isCurrentUserOwner = $team->isOwner($currentUser);
        $targetUserCurrentRole = $team->getUserRole($user);
        $newRole = $request->input('role');

        // Only owners can promote members to admin
        if ($newRole === 'admin' && ! $isCurrentUserOwner) {
            return back()->withErrors([
                'role' => 'Only team owners can promote members to administrator.',
            ]);
        }

        // Only owners can demote admins to members (prevents admin-to-admin demotion attacks)
        if ($targetUserCurrentRole === 'admin' && $newRole === 'member' && ! $isCurrentUserOwner) {
            return back()->withErrors([
                'role' => 'Only team owners can demote administrators.',
            ]);
        }

        $team->members()->updateExistingPivot($user->id, [
            'role' => $newRole,
        ]);

        return redirect()->route('teams.members', $team)
            ->with('success', 'Member role updated successfully!');
    }

    /**
     * Remove a member from the team.
     */
    public function destroy(Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

        if ($team->isOwner($user)) {
            return back()->withErrors(['error' => 'Cannot remove the team owner.']);
        }

        $team->members()->detach($user->id);

        return redirect()->route('teams.members', $team)
            ->with('success', 'Member removed successfully!');
    }

    /**
     * Leave the team.
     */
    public function leave(Request $request, Team $team)
    {
        $user = $request->user();

        if ($team->isOwner($user)) {
            return back()->withErrors(['error' => 'Team owners cannot leave. Transfer ownership or delete the team instead.']);
        }

        if (! $team->hasMember($user)) {
            return back()->withErrors(['error' => 'You are not a member of this team.']);
        }

        $team->members()->detach($user->id);

        return redirect()->route('teams.index')
            ->with('success', 'You have left the team.');
    }
}
