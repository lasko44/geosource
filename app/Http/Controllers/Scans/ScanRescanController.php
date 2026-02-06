<?php

namespace App\Http\Controllers\Scans;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scans\RescanRequest;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Creates a new scan based on a previous scan's URL.
 */
class ScanRescanController extends Controller
{
    public function __invoke(RescanRequest $request, Scan $scan, ScanService $scanService): JsonResponse|RedirectResponse
    {
        try {
            $newScan = $scanService->executeRescan(
                $scan,
                $request->user(),
                $request->getTier(),
                $request
            );

            $scanService->dispatchScan($newScan);

            if ($request->wantsJson()) {
                return response()->json([
                    'uuid' => $newScan->uuid,
                    'url' => $newScan->url,
                    'status' => $newScan->status,
                ]);
            }

            return redirect()->route('scans.show', $newScan);
        } catch (QuotaExceededException $e) {
            $errorKey = $e->getQuotaType() === 'access' ? 'access' : 'limit';

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return redirect()->route('scans.show', $scan)->withErrors([$errorKey => $e->getMessage()]);
        }
    }
}
