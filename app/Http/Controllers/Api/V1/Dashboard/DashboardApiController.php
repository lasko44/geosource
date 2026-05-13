<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Citation\CitationService;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Returns dashboard overview data via the API.
 */
class DashboardApiController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboardService,
        CitationService $citationService
    ): JsonResponse {
        $user = $request->user();

        $teamContext = $dashboardService->resolveTeamContext($user, $request);

        $currentTeamId = Arr::get($teamContext, 'currentTeamId');
        $currentTeam = Arr::get($teamContext, 'currentTeam');

        $scanQuery = $dashboardService->buildDashboardScanQuery($user, $currentTeamId, $currentTeam);

        $recentScans = (clone $scanQuery)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'recent_scans' => $recentScans,
            'stats' => $dashboardService->buildStats($scanQuery),
            'usage' => $dashboardService->getUsageSummary($user, $currentTeam),
            'current_team' => $dashboardService->formatCurrentTeam($currentTeam),
            'citation_data' => $dashboardService->buildCitationData(
                $user,
                $currentTeamId,
                $citationService->canAccessCitations($user)
            ),
        ]);
    }
}
