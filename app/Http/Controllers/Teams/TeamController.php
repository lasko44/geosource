<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('teams', 'slug'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $slug = $request->input('slug') ?: Str::slug($request->input('name'));

        // Ensure slug is unique
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
                    'email' => $team->owner->email,
                ],
                'members_count' => $team->members->count(),
            ],
            'userRole' => $team->getUserRole($user),
            'isOwner' => $team->isOwner($user),
            'isAdmin' => $team->isAdmin($user),
            'hasSubscription' => $team->subscribed('default'),
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('teams', 'slug')->ignore($team->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team->update([
            'name' => $request->input('name'),
            'slug' => $request->input('slug') ?: Str::slug($request->input('name')),
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

        // Cancel any active subscription first
        if ($team->subscribed('default')) {
            $team->subscription('default')->cancelNow();
        }

        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully!');
    }
}
