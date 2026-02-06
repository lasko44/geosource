<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledScans\StoreScheduledScanRequest;
use App\Http\Requests\ScheduledScans\UpdateScheduledScanRequest;
use App\Models\ScheduledScan;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages scheduled scans for automated recurring website analysis.
 */
class ScheduledScanController extends Controller
{
    /**
     * Display listing of scheduled scans.
     */
    public function index(Request $request): Response
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
    public function create(Request $request): Response|RedirectResponse
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
    public function store(StoreScheduledScanRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

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

        $url = $request->getUrl();
        $frequency = $request->getFrequency();

        $scheduledScan = ScheduledScan::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'url' => $url,
            'name' => $request->getName() ?? parse_url($url, PHP_URL_HOST),
            'frequency' => $frequency,
            'scheduled_time' => $request->getScheduledTime(),
            'day_of_week' => $frequency === 'weekly' ? ($request->getDayOfWeek() ?? 1) : null,
            'day_of_month' => $frequency === 'monthly' ? ($request->getDayOfMonth() ?? 1) : null,
            'is_active' => true,
        ]);

        return redirect()->route('scheduled-scans.index')
            ->with('success', 'Scheduled scan created successfully.');
    }

    /**
     * Show edit form.
     *
     * @throws AuthorizationException
     */
    public function edit(ScheduledScan $scheduledScan): Response|RedirectResponse
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
     *
     * @throws AuthorizationException
     */
    public function update(UpdateScheduledScanRequest $request, ScheduledScan $scheduledScan): RedirectResponse
    {
        $this->authorize('update', $scheduledScan);

        $user = $request->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        $url = $request->getUrl();
        $frequency = $request->getFrequency();

        $scheduledScan->update([
            'url' => $url,
            'name' => $request->getName() ?? parse_url($url, PHP_URL_HOST),
            'frequency' => $frequency,
            'scheduled_time' => $request->getScheduledTime(),
            'day_of_week' => $frequency === 'weekly' ? ($request->getDayOfWeek() ?? 1) : null,
            'day_of_month' => $frequency === 'monthly' ? ($request->getDayOfMonth() ?? 1) : null,
            'is_active' => $request->getIsActive(),
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
    public function toggle(ScheduledScan $scheduledScan): RedirectResponse
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
    public function destroy(ScheduledScan $scheduledScan): RedirectResponse
    {
        $this->authorize('delete', $scheduledScan);

        $scheduledScan->delete();

        return redirect()->route('scheduled-scans.index')
            ->with('success', 'Scheduled scan deleted successfully.');
    }

    /**
     * Run a scheduled scan manually.
     */
    public function runNow(ScheduledScan $scheduledScan, SubscriptionService $subscriptionService, TokenService $tokenService): RedirectResponse
    {
        $this->authorize('update', $scheduledScan);

        $user = auth()->user();

        if (! $user->hasFeature('scheduled_scans')) {
            return back()->withErrors(['feature' => 'Scheduled scans are not available on your current plan.']);
        }

        // Check quota
        if ($scheduledScan->team_id) {
            $team = $scheduledScan->team;
            if (! $subscriptionService->canScanForTeam($team)) {
                return back()->withErrors(['quota' => 'Team scan quota exceeded.']);
            }
        } else {
            if (! $subscriptionService->canScan($user)) {
                return back()->withErrors(['quota' => 'Personal scan quota exceeded.']);
            }
        }

        // Get token cost for scheduled scan
        $tokenCost = config('tokens.costs.scheduled_scan', 5);

        // Check if user has enough tokens (admins skip)
        if (! $user->is_admin && $tokenCost > 0 && ($user->token_balance ?? 0) < $tokenCost) {
            return back()->withErrors([
                'tokens' => "You need {$tokenCost} tokens to run a scheduled scan. Please purchase more tokens.",
            ]);
        }

        // Use transaction with pessimistic locking to prevent race conditions
        $scan = DB::transaction(function () use ($scheduledScan, $user, $tokenCost) {
            $tokensCharged = false;
            $tokensAmount = 0;

            // Deduct tokens if not admin
            if (! $user->is_admin && $tokenCost > 0) {
                // Re-lock user to verify balance
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if (($lockedUser->token_balance ?? 0) < $tokenCost) {
                    throw new \Exception('Insufficient tokens after lock verification.');
                }

                $tokenService->spend($lockedUser, 'scheduled_scan', [
                    'scheduled_scan_id' => $scheduledScan->id,
                    'url' => $scheduledScan->url,
                ]);

                $tokensCharged = true;
                $tokensAmount = $tokenCost;
            }

            // Create scan record with token tracking
            return \App\Models\Scan::create([
                'user_id' => $scheduledScan->user_id,
                'team_id' => $scheduledScan->team_id,
                'scheduled_scan_id' => $scheduledScan->id,
                'url' => $scheduledScan->url,
                'title' => $scheduledScan->name ?? parse_url($scheduledScan->url, PHP_URL_HOST),
                'status' => 'pending',
                'tokens_charged' => $tokensCharged,
                'tokens_amount' => $tokensAmount,
            ]);
        });

        \App\Jobs\ScanWebsiteJob::dispatch($scan);

        // Update run stats (but don't change next_run_at for manual runs)
        $scheduledScan->last_run_at = now();
        $scheduledScan->total_runs++;
        $scheduledScan->save();

        return redirect()->route('scans.show', $scan)
            ->with('success', 'Scan started.');
    }
}
