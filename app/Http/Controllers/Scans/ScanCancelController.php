<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;

class ScanCancelController extends Controller
{
    public function __invoke(Scan $scan, Request $request, ScanService $scanService)
    {
        $this->authorize('update', $scan);

        if (! $scanService->cancelScan($scan)) {
            $error = 'Only pending or processing scans can be cancelled.';

            if ($request->wantsJson()) {
                return response()->json(['error' => $error], 422);
            }

            return back()->withErrors(['status' => $error]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'cancelled',
            ]);
        }

        return back()->with('success', 'Scan cancelled successfully.');
    }
}
