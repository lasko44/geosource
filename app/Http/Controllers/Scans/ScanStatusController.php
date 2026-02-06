<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;

/**
 * Returns the current status of a scan for polling.
 */
class ScanStatusController extends Controller
{
    public function __invoke(Scan $scan, ScanService $scanService): JsonResponse
    {
        $this->authorize('view', $scan);

        return response()->json($scanService->getScanStatusData($scan));
    }
}
