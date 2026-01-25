<?php

namespace App\Http\Controllers;

use App\Models\ScheduledScan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduledScanController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Display listing of scheduled scans.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user has scheduled scans feature
        if (! $user->hasFeature('scheduled_scans')) {
            return Inertia::render('ScheduledScans/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        // Get current team context
        $currentTeamId = session('current_team_id');
        $currentTeamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

        // Build query based on team context
        if ($currentTeamId) {
            $scheduledScans = ScheduledScan::where('team_id', $currentTeamId)
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->get();
        } else {
            $scheduledScans = ScheduledScan::where('user_id', $user->id)
                ->whereNull('team_id')
                ->orderByDesc('created_at')
                ->get();
        }

        // Get the last 10 scans that were created from scheduled scans
        $scheduledScanIds = $scheduledScans->pluck('id');
        $recentScheduledRuns = \App\Models\Scan::whereIn('scheduled_scan_id', $scheduledScanIds)
            ->with(['user:id,name', 'scheduledScan:id,name,url'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('ScheduledScans/Index', [
            'scheduledScans' => $scheduledScans->map(fn ($scan) => [
                'id' => $scan->id,
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'name' => $scan->name,
                'frequency' => $scan->frequency,
                'scheduled_time' => $scan->scheduled_time?->format('H:i'),
                'day_of_week' => $scan->day_of_week,
                'day_of_month' => $scan->day_of_month,
                'is_active' => $scan->is_active,
                'last_run_at' => $scan->last_run_at?->toIso8601String(),
                'next_run_at' => $scan->next_run_at?->toIso8601String(),
                'total_runs' => $scan->total_runs,
                'schedule_description' => $scan->schedule_description,
                'user' => $scan->user ? ['name' => $scan->user->name] : null,
                'created_at' => $scan->created_at->toIso8601String(),
            ]),
            'recentScheduledRuns' => $recentScheduledRuns->map(fn ($scan) => [
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'title' => $scan->title,
                'score' => $scan->score,
                'grade' => $scan->grade,
                'status' => $scan->status,
                'scheduled_scan_name' => $scan->scheduledScan?->name,
                'user' => $scan->user ? ['name' => $scan->user->name] : null,
                'created_at' => $scan->created_at->toIso8601String(),
            ]),
            'currentTeamId' => $currentTeamId,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return redirect()->route('scheduled-scans.index');
        }

        $currentTeamId = session('current_team_id');
        $currentTeamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

        return Inertia::render('ScheduledScans/Create', [
            'currentTeamId' => $currentTeamId,
        ]);
    }

    /**
     * Store a new scheduled scan.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        $validated = $request->validate([
            'url' => 'required|url',
            'name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'scheduled_time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
        ]);

        // Get team context
        $currentTeamId = session('current_team_id');
        $teamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

        // Validate team access
        if ($teamId) {
            $team = $user->allTeams()->firstWhere('id', $teamId);
            if (! $team) {
                return back()->withErrors(['team' => 'You do not have access to this team.']);
            }
        }

        $scheduledScan = ScheduledScan::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'url' => $validated['url'],
            'name' => $validated['name'] ?? parse_url($validated['url'], PHP_URL_HOST),
            'frequency' => $validated['frequency'],
            'scheduled_time' => $validated['scheduled_time'],
            'day_of_week' => $validated['frequency'] === 'weekly' ? ($validated['day_of_week'] ?? 1) : null,
            'day_of_month' => $validated['frequency'] === 'monthly' ? ($validated['day_of_month'] ?? 1) : null,
            'is_active' => true,
        ]);

        return redirect()->route('scheduled-scans.index')
            ->with('success', 'Scheduled scan created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(ScheduledScan $scheduledScan)
    {
        $this->authorize('update', $scheduledScan);

        $user = auth()->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return redirect()->route('scheduled-scans.index');
        }

        return Inertia::render('ScheduledScans/Edit', [
            'scheduledScan' => [
                'uuid' => $scheduledScan->uuid,
                'url' => $scheduledScan->url,
                'name' => $scheduledScan->name,
                'frequency' => $scheduledScan->frequency,
                'scheduled_time' => $scheduledScan->scheduled_time?->format('H:i'),
                'day_of_week' => $scheduledScan->day_of_week,
                'day_of_month' => $scheduledScan->day_of_month,
                'is_active' => $scheduledScan->is_active,
            ],
        ]);
    }

    /**
     * Update scheduled scan.
     */
    public function update(Request $request, ScheduledScan $scheduledScan)
    {
        $this->authorize('update', $scheduledScan);

        $user = $request->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        $validated = $request->validate([
            'url' => 'required|url',
            'name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'scheduled_time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'is_active' => 'boolean',
        ]);

        $scheduledScan->update([
            'url' => $validated['url'],
            'name' => $validated['name'] ?? parse_url($validated['url'], PHP_URL_HOST),
            'frequency' => $validated['frequency'],
            'scheduled_time' => $validated['scheduled_time'],
            'day_of_week' => $validated['frequency'] === 'weekly' ? ($validated['day_of_week'] ?? 1) : null,
            'day_of_month' => $validated['frequency'] === 'monthly' ? ($validated['day_of_month'] ?? 1) : null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Recalculate next run time
        $scheduledScan->next_run_at = $scheduledScan->calculateNextRunAt();
        $scheduledScan->save();

        return redirect()->route('scheduled-scans.index')
            ->with('success', 'Scheduled scan updated successfully.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(ScheduledScan $scheduledScan)
    {
        $this->authorize('update', $scheduledScan);

        $user = auth()->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        $scheduledScan->is_active = ! $scheduledScan->is_active;

        if ($scheduledScan->is_active) {
            $scheduledScan->next_run_at = $scheduledScan->calculateNextRunAt();
        }

        $scheduledScan->save();

        return back()->with('success', $scheduledScan->is_active ? 'Scheduled scan activated.' : 'Scheduled scan paused.');
    }

    /**
     * Delete scheduled scan.
     */
    public function destroy(ScheduledScan $scheduledScan)
    {
        $this->authorize('delete', $scheduledScan);

        $scheduledScan->delete();

        return redirect()->route('scheduled-scans.index')
            ->with('success', 'Scheduled scan deleted successfully.');
    }

    /**
     * Run a scheduled scan manually.
     */
    public function runNow(ScheduledScan $scheduledScan)
    {
        $this->authorize('update', $scheduledScan);

        $user = auth()->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        // Check quota
        if ($scheduledScan->team_id) {
            $team = $scheduledScan->team;
            if (! $this->subscriptionService->canScanForTeam($team)) {
                return back()->withErrors(['quota' => 'Team scan quota exceeded.']);
            }
        } else {
            if (! $this->subscriptionService->canScan($user)) {
                return back()->withErrors(['quota' => 'Personal scan quota exceeded.']);
            }
        }

        // Create and dispatch scan
        $scan = \App\Models\Scan::create([
            'user_id' => $scheduledScan->user_id,
            'team_id' => $scheduledScan->team_id,
            'scheduled_scan_id' => $scheduledScan->id,
            'url' => $scheduledScan->url,
            'title' => $scheduledScan->name ?? parse_url($scheduledScan->url, PHP_URL_HOST),
            'status' => 'pending',
        ]);

        \App\Jobs\ScanWebsiteJob::dispatch($scan);

        // Update run stats (but don't change next_run_at for manual runs)
        $scheduledScan->last_run_at = now();
        $scheduledScan->total_runs++;
        $scheduledScan->save();

        return redirect()->route('scans.show', $scan)
            ->with('success', 'Scan started.');
    }
}
