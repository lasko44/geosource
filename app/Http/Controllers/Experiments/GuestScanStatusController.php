<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns scan status for guest polling, verified by visitor cookie.
 */
class GuestScanStatusController extends Controller
{
    public function __invoke(Scan $scan, Request $request, ScanService $scanService): JsonResponse
    {
        $visitorId = $request->cookie('ab_visitor');

        if (! $visitorId || $scan->visitor_id !== $visitorId) {
            throw new NotFoundHttpException;
        }

        return response()->json($scanService->getScanStatusData($scan));
    }
}
