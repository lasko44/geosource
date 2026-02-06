<?php

namespace App\Http\Controllers\Citations;

use App\Http\Controllers\Controller;
use App\Services\Citation\CitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitationTrendController extends Controller
{
    /**
     * Get citation trends data.
     */
    public function index(Request $request, CitationService $citationService): JsonResponse
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $days = min(max((int) $request->input('days', 30), 7), 90);
        $team = $citationService->getCurrentTeam($user);
        $trends = $citationService->getTrends($user, $team, $days);

        return response()->json($trends);
    }
}
