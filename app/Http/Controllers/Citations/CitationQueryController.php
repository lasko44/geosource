<?php

namespace App\Http\Controllers\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citations\StoreCitationQueryRequest;
use App\Http\Requests\Citations\UpdateCitationQueryRequest;
use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages citation query CRUD operations.
 */
class CitationQueryController extends Controller
{
    /**
     * Display the citation tracking dashboard.
     */
    public function index(Request $request, CitationService $citationService): Response
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return Inertia::render('Citations/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        $team = $citationService->getCurrentTeam($user);

        return Inertia::render('Citations/Index', [
            'queries' => $citationService->getQueriesForUser($user, $team),
            'recentChecks' => $citationService->getRecentChecks($user, $team),
            'alerts' => $citationService->getUnreadAlerts($user, $team),
            'usage' => $citationService->getUsageSummary($user, $team),
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
    public function create(Request $request, CitationService $citationService): Response
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return Inertia::render('Citations/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        return Inertia::render('Citations/Queries/Create', [
            'usage' => $citationService->getUsageSummary($user),
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
    public function store(StoreCitationQueryRequest $request, CitationService $citationService): \Illuminate\Http\RedirectResponse
    {
        $citationQuery = $citationService->createQuery(
            $request->user(),
            $request->input('query'),
            $request->input('domain'),
            $request->input('brand'),
            $request->input('frequency'),
            $request->getTeam(),
            $request->input('scheduled_platforms', []),
            $request->input('monthly_token_budget')
        );

        return redirect()->route('citations.queries.show', $citationQuery)
            ->with('success', 'Citation query created successfully.');
    }

    /**
     * Show a citation query with its check history.
     */
    public function show(CitationQuery $query, Request $request, CitationService $citationService): Response
    {
        $this->authorize('view', $query);

        $query->load([
            'checks' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(50),
            'alerts' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(20),
        ]);

        return Inertia::render('Citations/Queries/Show', [
            'query' => $query,
            'usage' => $citationService->getUsageSummary($request->user()),
            'platforms' => config('citations.platforms'),
            'availablePlatforms' => $citationService->getAvailablePlatforms($request->user()),
            'trendData' => $citationService->buildQueryTrendData($query),
        ]);
    }

    /**
     * Update a citation query.
     */
    public function update(UpdateCitationQueryRequest $request, CitationQuery $query): \Illuminate\Http\RedirectResponse
    {
        $query->update($request->validated());

        if ($request->has('frequency') && $request->frequency !== 'manual') {
            $query->scheduleNextCheck();
        }

        return back()->with('success', 'Query updated successfully.');
    }

    /**
     * Delete a citation query.
     */
    public function destroy(CitationQuery $query)
    {
        $this->authorize('delete', $query);

        $query->delete();

        return redirect()->route('citations.index')
            ->with('success', 'Citation query deleted successfully.');
    }
}
