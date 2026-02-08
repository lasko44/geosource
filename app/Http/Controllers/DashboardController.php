<?php

namespace App\Http\Controllers;

use App\Services\Citation\CitationService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the main user dashboard with scans, stats, and team context.
 */
class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(
        Request $request,
        DashboardService $dashboardService,
        CitationService $citationService
    ): Response
    {
        $user = $request->user();

        $teamContext = $dashboardService->resolveTeamContext($user, $request);

        $teams = Arr::get($teamContext, 'teams');
        $currentTeamId = Arr::get($teamContext, 'currentTeamId');
        $currentTeam = Arr::get($teamContext, 'currentTeam');
        $hasPersonalOption = Arr::get($teamContext, 'hasPersonalOption');

        $scanQuery = $dashboardService->buildDashboardScanQuery($user, $currentTeamId, $currentTeam);

        // Clone the base query so modifications below (eager loads, ordering, limit)
        // don't mutate the original $scanQuery used later (for stats/usage, etc.).
        $recentScans = (clone $scanQuery)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'recentScans' => $recentScans,
            'stats' => $dashboardService->buildStats($scanQuery),
            'usage' => $dashboardService->getUsageSummary($user, $currentTeam),
            'showUpgradePrompt' => $user->shouldShowUpgradePrompt(),
            'plans' => config('billing.plans.user'),
            'teams' => $teams,
            'currentTeamId' => $currentTeamId,
            'currentTeam' => $dashboardService->formatCurrentTeam($currentTeam),
            'hasPersonalOption' => $hasPersonalOption,
            'citationData' => $dashboardService->buildCitationData(
                $user,
                $currentTeamId,
                $citationService->canAccessCitations($user)
            ),
            'canBulkScan' => $user->hasFeature('bulk_scanning') || ($user->token_balance ?? 0) > 0,
        ]);
    }
}
