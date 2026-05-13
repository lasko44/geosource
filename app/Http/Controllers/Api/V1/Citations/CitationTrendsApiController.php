<?php

namespace App\Http\Controllers\Api\V1\Citations;

use App\Http\Controllers\Controller;
use App\Services\Citation\CitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns citation trend data via the API.
 */
class CitationTrendsApiController extends Controller
{
    public function __invoke(Request $request, CitationService $citationService): JsonResponse
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Citation access requires tokens or a paid plan.'], 403);
        }

        $days = min(max((int) $request->input('days', 30), 7), 90);
        $teamId = $request->input('team_id');
        $team = $teamId ? $request->user()->allTeams()->firstWhere('id', (int) $teamId) : null;
        $trends = $citationService->getTrends($user, $team, $days);

        return response()->json($trends);
    }
}
