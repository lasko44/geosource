<?php

namespace App\Http\Controllers\Api\V1\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCitationQueryApiRequest;
use App\Services\Citation\CitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles citation query listing and creation via the API.
 */
class CitationQueryApiController extends Controller
{
    /**
     * List citation queries for the authenticated user.
     */
    public function index(Request $request, CitationService $citationService): JsonResponse
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Citation access requires tokens or a paid plan.'], 403);
        }

        $teamId = $request->input('team_id');
        $team = $teamId ? $request->user()->allTeams()->firstWhere('id', (int) $teamId) : null;

        return response()->json([
            'queries' => $citationService->getQueriesForUser($user, $team),
            'usage' => $citationService->getUsageSummary($user, $team),
        ]);
    }

    /**
     * Create a new citation query.
     */
    public function store(StoreCitationQueryApiRequest $request, CitationService $citationService): JsonResponse
    {
        $citationQuery = $citationService->createQuery(
            $request->user(),
            $request->input('query'),
            $request->input('domain'),
            $request->input('brand'),
            $request->input('frequency', 'manual'),
            $request->getValidatedTeam(),
            $request->input('scheduled_platforms', []),
            $request->input('monthly_token_budget')
        );

        return response()->json($citationQuery, 201);
    }
}
