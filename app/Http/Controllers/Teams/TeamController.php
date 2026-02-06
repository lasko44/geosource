<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamRequest;
use App\Http\Requests\Teams\TransferOwnershipRequest;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Models\Team;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages team creation, settings, and ownership.
 */
class TeamController extends Controller
{
    /**
     * Show the list of teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('teams/Index', [
            'ownedTeams' => $user->ownedTeams()->with('members')->get()->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'members_count' => $team->members->count(),
                'is_owner' => true,
                'role' => 'owner',
            ]),
            'memberTeams' => $user->teams()->with('owner')->get()->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'owner' => [
                    'id' => $team->owner->id,
                    'name' => $team->owner->name,
                ],
                'is_owner' => false,
                'role' => $team->pivot->role,
            ]),
        ]);
    }

    /**
     * Show the create team form.
     */
    public function create(): Response
    {
        return Inertia::render('teams/Create');
    }

    /**
     * Store a new team.
     */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $user = $request->user();
        $subscriptionService = app(SubscriptionService::class);

        // Check if user can create teams
        if (! $subscriptionService->canCreateTeams($user)) {
            $teamsAllowed = $subscriptionService->getTeamsAllowed($user);

            if ($teamsAllowed === 0) {
                return back()->withErrors([
                    'team' => 'Your current plan does not allow creating teams. Please upgrade to Pro or Agency.',
                ]);
            }

            return back()->withErrors([
                'team' => 'You have reached your team limit. Please upgrade your plan to create more teams.',
            ]);
        }

        $slug = $request->getSlug() ?: Str::slug($request->getName());

        // Ensure slug is globally unique for route resolution
        $originalSlug = $slug;
        $counter = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => $request->getName(),
            'slug' => $slug,
            'description' => $request->getDescription(),
        ]);

        // Add owner as a member with owner role
        $team->members()->attach($user->id, ['role' => 'owner']);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team created successfully!');
    }

    /**
     * Show the team details.
     */
    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $user = auth()->user();

        return Inertia::render('teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'created_at' => $team->created_at->toISOString(),
                'owner' => [
                    'id' => $team->owner->id,
                    'name' => $team->owner->name,
                ],
                'members_count' => $team->members->count(),
            ],
            'userRole' => $team->getUserRole($user),
            'isOwner' => $team->isOwner($user),
            'isAdmin' => $team->isAdmin($user),
            'hasWhiteLabel' => $team->hasWhiteLabel(),
            'hasSubscription' => $team->owner->subscribed(),
        ]);
    }

    /**
     * Show the edit team form.
     */
    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('teams/Edit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
            ],
        ]);
    }

    /**
     * Update the team.
     */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $slug = $request->getSlug() ?: Str::slug($request->getName());

        // Ensure slug is globally unique for route resolution
        $originalSlug = $slug;
        $counter = 1;
        while (Team::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $team->update([
            'name' => $request->getName(),
            'slug' => $slug,
            'description' => $request->getDescription(),
        ]);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team updated successfully!');
    }

    /**
     * Delete the team.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        // Clean up related resources before soft delete
        // Delete pending invitations (they become invalid)
        $team->invitations()->delete();

        // Detach all members from the team
        $team->members()->detach();

        // Soft delete the team
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully!');
    }

    /**
     * Transfer team ownership to another member.
     */
    public function transferOwnership(TransferOwnershipRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('delete', $team); // Only owner can transfer

        $newOwnerId = $request->getNewOwnerId();

        // Ensure new owner is a member of the team
        if (! $team->members()->where('user_id', $newOwnerId)->exists()) {
            return back()->withErrors(['user_id' => 'The selected user is not a member of this team.']);
        }

        // Verify new owner has a subscription that allows team ownership
        $newOwner = \App\Models\User::find($newOwnerId);
        $subscriptionService = app(SubscriptionService::class);

        if (! $subscriptionService->canCreateTeams($newOwner)) {
            $teamsAllowed = $subscriptionService->getTeamsAllowed($newOwner);

            if ($teamsAllowed === 0) {
                return back()->withErrors([
                    'user_id' => 'This user does not have a subscription that allows team ownership. They must upgrade to Pro or Agency first.',
                ]);
            }

            return back()->withErrors([
                'user_id' => 'This user has reached their team limit and cannot take ownership of another team.',
            ]);
        }

        $currentOwnerId = $team->owner_id;

        // Update the team owner
        $team->update(['owner_id' => $newOwnerId]);

        // Update roles: new owner becomes 'owner', old owner becomes 'admin'
        $team->members()->updateExistingPivot($newOwnerId, ['role' => 'owner']);
        $team->members()->updateExistingPivot($currentOwnerId, ['role' => 'admin']);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team ownership transferred successfully!');
    }
}
