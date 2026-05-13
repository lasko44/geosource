<?php

namespace App\Http\Controllers\Api\V1\Scans;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;

/**
 * Cancels a pending or processing scan via the API.
 */
class ScanCancelApiController extends Controller
{
    public function __invoke(Scan $scan, ScanService $scanService): JsonResponse
    {
        $this->authorize('update', $scan);

        if (! $scanService->cancelScan($scan)) {
            return response()->json([
                'error' => 'Only pending or processing scans can be cancelled.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'cancelled',
        ]);
    }
}
