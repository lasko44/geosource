<?php

namespace App\Http\Controllers;

use App\Jobs\CheckCitationJob;
use App\Models\CitationAlert;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Services\Citation\CitationService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CitationController extends Controller
{
    public function __construct(
        private CitationService $citationService,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Display the citation tracking dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check access
        if (! $this->citationService->canAccessCitations($user)) {
            return Inertia::render('Citations/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        // Get queries based on context
        $queriesBuilder = CitationQuery::where('user_id', $user->id);
        if ($team) {
            $queriesBuilder->where('team_id', $team->id);
        } else {
            $queriesBuilder->whereNull('team_id');
        }

        $queries = $queriesBuilder
            ->with(['checks' => function ($query) {
                $query->where('status', CitationCheck::STATUS_COMPLETED)
                    ->orderBy('created_at', 'desc')
                    ->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unread alerts
        $unreadAlerts = $this->citationService->getUnreadAlerts($user, $team);

        // Get usage summary
        $usage = $this->citationService->getUsageSummary($user, $team);

        // Get recent checks
        $recentChecks = CitationCheck::where('user_id', $user->id)
            ->when($team, fn ($q) => $q->where('team_id', $team->id))
            ->when(! $team, fn ($q) => $q->whereNull('team_id'))
            ->with('citationQuery')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Citations/Index', [
            'queries' => $queries,
            'recentChecks' => $recentChecks,
            'alerts' => $unreadAlerts,
            'usage' => $usage,
            'platforms' => config('citations.platforms'),
            'currentTeam' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
            ] : null,
        ]);
    }

    /**
     * Show the create query form.
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if (! $this->citationService->canAccessCitations($user)) {
            return redirect()->route('citations.index');
        }

        $usage = $this->citationService->getUsageSummary($user);

        return Inertia::render('Citations/Queries/Create', [
            'usage' => $usage,
            'platforms' => config('citations.platforms'),
            'frequencies' => [
                CitationQuery::FREQUENCY_MANUAL => 'Manual only',
                CitationQuery::FREQUENCY_WEEKLY => 'Weekly',
                CitationQuery::FREQUENCY_DAILY => 'Daily',
            ],
        ]);
    }

    /**
     * Store a new citation query.
     */
    public function store(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i'],
            'brand' => 'nullable|string|max:255',
            'frequency' => 'required|string|in:manual,daily,weekly',
        ], [
            'domain.regex' => 'Please enter a valid domain (e.g., example.com).',
        ]);

        $user = $request->user();

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        // Check if user can create queries
        if (! $this->citationService->canCreateQuery($user, $team)) {
            return back()->withErrors([
                'limit' => 'You have reached your citation query limit. Please upgrade your plan.',
            ]);
        }

        // Validate frequency access
        $availableFrequencies = $this->citationService->getAvailableFrequencies($user);
        if (! in_array($request->frequency, $availableFrequencies)) {
            return back()->withErrors([
                'frequency' => 'This frequency is not available on your current plan.',
            ]);
        }

        // Create the query
        $citationQuery = $this->citationService->createQuery(
            $user,
            $request->input('query'),
            $request->input('domain'),
            $request->input('brand'),
            $request->input('frequency'),
            $team
        );

        return redirect()->route('citations.queries.show', $citationQuery)
            ->with('success', 'Citation query created successfully.');
    }

    /**
     * Show a citation query with its check history.
     */
    public function show(CitationQuery $query, Request $request)
    {
        $user = $request->user();

        // Authorization: user must own the query or be a member of the query's team
        if ($query->user_id !== $user->id) {
            // Not the owner - must be a team member to access
            if (! $query->team_id || ! $user->allTeams()->contains('id', $query->team_id)) {
                abort(403);
            }
        }

        $query->load([
            'checks' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(50);
            },
            'alerts' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(20);
            },
        ]);

        $usage = $this->citationService->getUsageSummary($user);

        return Inertia::render('Citations/Queries/Show', [
            'query' => $query,
            'usage' => $usage,
            'platforms' => config('citations.platforms'),
            'availablePlatforms' => $this->citationService->getAvailablePlatforms($user),
        ]);
    }

    /**
     * Run a manual citation check.
     */
    public function check(CitationQuery $query, Request $request)
    {
        $request->validate([
            'platform' => 'required|string',
        ]);

        $user = $request->user();

        // Authorization: user must own the query or be a member of the query's team
        if ($query->user_id !== $user->id) {
            // Not the owner - must be a team member to access
            if (! $query->team_id || ! $user->allTeams()->contains('id', $query->team_id)) {
                abort(403);
            }
        }

        // Check quota
        if (! $this->citationService->canPerformCheck($user)) {
            return back()->withErrors([
                'limit' => 'You have reached your daily check limit. Please try again tomorrow.',
            ]);
        }

        // Check platform access
        $availablePlatforms = $this->citationService->getAvailablePlatforms($user);
        if (! in_array($request->platform, $availablePlatforms)) {
            return back()->withErrors([
                'platform' => 'This platform is not available on your current plan.',
            ]);
        }

        try {
            $check = DB::transaction(function () use ($user, $query, $request) {
                // Lock user for quota check
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // Re-verify quota within transaction
                if (! $this->citationService->canPerformCheck($lockedUser)) {
                    throw new \Exception('Daily check limit reached.');
                }

                // Create the check
                return $this->citationService->createCheck($query, $request->platform, $lockedUser);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['limit' => $e->getMessage()]);
        }

        // Dispatch the job
        CheckCitationJob::dispatch($check);

        return redirect()->route('citations.queries.show', $query)
            ->with('activeCheck', $check->uuid);
    }

    /**
     * Get check status for polling.
     */
    public function checkStatus(CitationCheck $check, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($check->user_id !== $user->id) {
            if ($check->team_id && ! $user->allTeams()->contains('id', $check->team_id)) {
                abort(403);
            }
        }

        return response()->json([
            'status' => $check->status,
            'progress_step' => $check->progress_step,
            'progress_percent' => $check->progress_percent,
            'is_cited' => $check->is_cited,
            'error_message' => $check->error_message,
            'citations' => $check->citations,
            'completed_at' => $check->completed_at?->toISOString(),
        ]);
    }

    /**
     * Update a citation query.
     */
    public function update(CitationQuery $query, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($query->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'query' => 'sometimes|string|max:500',
            'domain' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i'],
            'brand' => 'nullable|string|max:255',
            'frequency' => 'sometimes|string|in:manual,daily,weekly',
            'is_active' => 'sometimes|boolean',
        ], [
            'domain.regex' => 'Please enter a valid domain (e.g., example.com).',
        ]);

        // Validate frequency if being changed
        if ($request->has('frequency')) {
            $availableFrequencies = $this->citationService->getAvailableFrequencies($user);
            if (! in_array($request->frequency, $availableFrequencies)) {
                return back()->withErrors([
                    'frequency' => 'This frequency is not available on your current plan.',
                ]);
            }
        }

        $query->update($request->only(['query', 'domain', 'brand', 'frequency', 'is_active']));

        // Reschedule if frequency changed
        if ($request->has('frequency') && $request->frequency !== 'manual') {
            $query->scheduleNextCheck();
        }

        return back()->with('success', 'Query updated successfully.');
    }

    /**
     * Delete a citation query.
     */
    public function destroy(CitationQuery $query, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($query->user_id !== $user->id) {
            abort(403);
        }

        $query->delete();

        return redirect()->route('citations.index')
            ->with('success', 'Citation query deleted successfully.');
    }

    /**
     * Get citation trends data.
     */
    public function trends(Request $request)
    {
        $user = $request->user();

        if (! $this->citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $days = (int) $request->input('days', 30);
        $days = min(max($days, 7), 90); // Between 7 and 90 days

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        $trends = $this->citationService->getTrends($user, $team, $days);

        return response()->json($trends);
    }

    /**
     * List alerts.
     */
    public function alerts(Request $request)
    {
        $user = $request->user();

        if (! $this->citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        $alertsQuery = CitationAlert::where('user_id', $user->id);

        if ($team) {
            $alertsQuery->where('team_id', $team->id);
        } else {
            $alertsQuery->whereNull('team_id');
        }

        $alerts = $alertsQuery
            ->with(['citationQuery', 'citationCheck'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($alerts);
    }

    /**
     * Mark alerts as read.
     */
    public function markAlertsRead(Request $request)
    {
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'integer',
        ]);

        $user = $request->user();

        $this->citationService->markAlertsAsRead($request->alert_ids, $user);

        return back();
    }
}
