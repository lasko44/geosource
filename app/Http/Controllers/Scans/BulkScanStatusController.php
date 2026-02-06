<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkScanStatusController extends Controller
{
    public function __invoke(Request $request, ScanService $scanService): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'required|string|uuid',
        ]);

        $scans = $scanService->getBulkScanStatus($validated['uuids'], $request->user()->id);

        return response()->json(['scans' => $scans]);
    }
}
