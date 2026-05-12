<?php

namespace App\Http\Controllers\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citations\StoreCitationResearchRequest;
use App\Models\CitationResearch;
use App\Services\Citation\CitationResearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages citation research CRUD operations.
 */
class CitationResearchController extends Controller
{
    /**
     * Display the list of user's citation research.
     */
    public function index(Request $request, CitationResearchService $researchService): Response
    {
        $user = $request->user();

        if (! $researchService->canAccessResearch($user)) {
            return Inertia::render('Citations/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        $team = $researchService->getCurrentTeam($user);

        return Inertia::render('Citations/Research/Index', [
            'research' => $researchService->getResearchForUser($user, $team),
            'usage' => $researchService->getUsageSummary($user),
            'currentTeam' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
            ] : null,
        ]);
    }

    /**
     * Show the create research form.
     */
    public function create(Request $request, CitationResearchService $researchService): Response
    {
        $user = $request->user();

        if (! $researchService->canAccessResearch($user)) {
            return Inertia::render('Citations/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        return Inertia::render('Citations/Research/Create', [
            'usage' => $researchService->getUsageSummary($user),
            'tiers' => [
                [
                    'value' => CitationResearch::TIER_RESEARCH,
                    'label' => 'Citation Research',
                    'description' => 'Query 5 AI platforms and analyze citation patterns',
                    'cost' => $researchService->getTokenCost(CitationResearch::TIER_RESEARCH),
                ],
                [
                    'value' => CitationResearch::TIER_RESEARCH_AUDIT,
                    'label' => 'Citation Research + Audit',
                    'description' => 'Research plus GEO scans of top 3 cited sources',
                    'cost' => $researchService->getTokenCost(CitationResearch::TIER_RESEARCH_AUDIT),
                ],
            ],
        ]);
    }

    /**
     * Store a new citation research.
     */
    public function store(StoreCitationResearchRequest $request, CitationResearchService $researchService): RedirectResponse
    {
        $research = $researchService->createResearch(
            $request->user(),
            $request->input('topic'),
            $request->getTier(),
            $request->getTeam()
        );

        $researchService->dispatchResearch($research);

        return redirect()->route('citations.research.show', $research)
            ->with('success', 'Citation research started. Results will be ready shortly.');
    }

    /**
     * Show a citation research with its results.
     */
    public function show(CitationResearch $research, Request $request, CitationResearchService $researchService): Response
    {
        $this->authorize('view', $research);

        // Load scans for audit tier
        if ($research->isAuditTier()) {
            $research->load('scans');
        }

        return Inertia::render('Citations/Research/Show', [
            'research' => $research,
            'usage' => $researchService->getUsageSummary($request->user()),
        ]);
    }

    /**
     * Delete a citation research.
     */
    public function destroy(CitationResearch $research): RedirectResponse
    {
        $this->authorize('delete', $research);

        $research->delete();

        return redirect()->route('citations.research.index')
            ->with('success', 'Citation research deleted successfully.');
    }
}
