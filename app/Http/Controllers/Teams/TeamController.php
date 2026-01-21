<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
    public function store(Request $request)
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

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->where('owner_id', $user->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:teams,slug', // Globally unique slug for route resolution
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $slug = $request->input('slug') ?: Str::slug($request->input('name'));

        // Ensure slug is globally unique for route resolution
        $originalSlug = $slug;
        $counter = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $team = Team::create([
            'owner_id' => $request->user()->id,
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
        ]);

        // Add owner as a member with owner role
        $team->members()->attach($request->user()->id, ['role' => 'owner']);

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
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->where('owner_id', $team->owner_id)->ignore($team->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('teams', 'slug')->ignore($team->id), // Globally unique slug
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $slug = $request->input('slug') ?: Str::slug($request->input('name'));

        // Ensure slug is globally unique for route resolution
        $originalSlug = $slug;
        $counter = 1;
        while (Team::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $team->update([
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
        ]);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team updated successfully!');
    }

    /**
     * Delete the team.
     */
    public function destroy(Team $team)
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
    public function transferOwnership(Request $request, Team $team)
    {
        $this->authorize('delete', $team); // Only owner can transfer

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $newOwnerId = $request->input('user_id');

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
