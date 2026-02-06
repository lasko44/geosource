<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scans\BulkScanStatusRequest;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;

/**
 * Returns the status of multiple scans for bulk polling.
 */
class BulkScanStatusController extends Controller
{
    public function __invoke(BulkScanStatusRequest $request, ScanService $scanService): JsonResponse
    {
        $scans = $scanService->getBulkScanStatus($request->getUuids(), $request->user()->id);

        return response()->json(['scans' => $scans]);
    }
}
