<?php

namespace App\Http\Controllers\Api\V1\Scans;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScanApiRequest;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles scan CRUD operations via the API.
 */
class ScanApiController extends Controller
{
    /**
     * List scans for the authenticated user.
     */
    public function index(Request $request, ScanService $scanService): JsonResponse
    {
        $this->authorize('viewAny', Scan::class);

        $user = $request->user();
        $teamId = $request->has('team_id') ? (int) $request->input('team_id') : null;

        $query = $scanService->buildScanListQuery($user, $teamId);

        $query = $scanService->applyScanFilters($query, [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'grade' => $request->input('grade'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);

        $query = $scanService->applyScanSorting(
            $query,
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        $perPage = $scanService->getValidatedPerPage((int) $request->input('per_page', 10));
        $scans = $query->with('user:id,name')->paginate($perPage);

        return response()->json($scans);
    }

    /**
     * Show a single scan with results.
     */
    public function show(Scan $scan, Request $request, ScanService $scanService): JsonResponse
    {
        $this->authorize('view', $scan);

        $scan->load(['user:id,name', 'discoveredCompetitors', 'parentScan:id,uuid,url,title,score,grade']);

        $user = $request->user();
        $scanData = $scan->toArray();

        if (isset($scanData['results']['pillars'])) {
            $scanData['results']['pillars'] = $scanService->filterPillarsForScanTier(
                $scanData['results']['pillars'],
                $scan,
                $user
            );
        }

        if (isset($scanData['results']['recommendations'])) {
            $scanData = $scanService->filterRecommendationsForDisplay($scanData, $user);
        }

        if (isset($scanData['results']['ai_suggestions'])) {
            $scanData['results']['ai_suggestions'] = $scanService->normalizeAiSuggestions(
                $scanData['results']['ai_suggestions']
            );
        }

        return response()->json($scanData);
    }

    /**
     * Submit a new scan.
     */
    public function store(StoreScanApiRequest $request, ScanService $scanService): JsonResponse
    {
        try {
            $scan = $scanService->executeScan(
                $request->user(),
                $request->input('url'),
                $request->input('tier', 'basic'),
                $request->getValidatedTeam(),
                $request,
            );

            $scanService->dispatchScan($scan);

            return response()->json([
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'status' => $scan->status,
                'requested_tier' => $scan->requested_tier,
            ], 201);
        } catch (QuotaExceededException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
