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

        return Inertia::render('teams/Members', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'owner' => [
                'id' => $team->owner->id,
                'name' => $team->owner->name,
                'email' => $team->owner->email,
            ],
            'members' => $team->members()->where('user_id', '!=', $team->owner_id)->get()->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->created_at->toISOString(),
            ]),
            'userRole' => $team->getUserRole($user),
            'isOwner' => $team->isOwner($user),
            'isAdmin' => $team->isAdmin($user),
        ]);
    }

    /**
     * Add a new member to the team.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);

        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($team->hasMember($user)) {
            return back()->withErrors(['email' => 'This user is already a member of the team.']);
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

        $team->members()->updateExistingPivot($user->id, [
            'role' => $request->input('role'),
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
