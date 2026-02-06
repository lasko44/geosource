<?php

namespace App\Http\Controllers\Scans;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scans\StoreBulkScanRequest;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles bulk scan creation for multiple URLs at once.
 */
class BulkScanController extends Controller
{
    /**
     * Display the bulk scan page.
     */
    public function index(Request $request, ScanService $scanService): Response
    {
        $user = $request->user();

        if (! $user->hasFeature('bulk_scanning')) {
            return Inertia::render('Scans/BulkUpgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        return Inertia::render('Scans/Bulk', [
            'currentTeamId' => $scanService->getCurrentTeamId(),
            'usage' => $user->getUsageSummary(),
        ]);
    }

    /**
     * Store newly created bulk scans.
     */
    public function store(StoreBulkScanRequest $request, ScanService $scanService): JsonResponse
    {
        try {
            $scans = $scanService->executeBulkScans(
                $request->user(),
                $request->getValidUrls(),
                $request->getTier(),
                $request->getValidatedTeam(),
                $request
            );

            $scanService->dispatchBulkScans($scans);

            return response()->json([
                'success' => true,
                'scans' => collect($scans)->map(fn ($scan) => [
                    'uuid' => $scan->uuid,
                    'url' => $scan->url,
                    'status' => $scan->status,
                ]),
                'skipped' => [
                    'cooldown' => count($request->getUrlsOnCooldown()),
                    'invalid' => count($request->getInvalidUrls()),
                ],
            ]);
        } catch (QuotaExceededException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
