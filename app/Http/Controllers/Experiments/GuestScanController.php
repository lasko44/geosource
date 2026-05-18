<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Experiments\GuestScanRequest;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\RedirectResponse;

/**
 * Runs a free guest scan without requiring authentication.
 * Limited to 3 scans per visitor via cookie-based tracking.
 */
class GuestScanController extends Controller
{
    private const MAX_GUEST_SCANS = 3;

    public function __invoke(GuestScanRequest $request, ScanService $scanService): RedirectResponse
    {
        $visitorId = $request->cookie('ab_visitor');

        if (! $visitorId) {
            return redirect()->route('register');
        }

        // Check how many scans this visitor has already run
        $guestScanCount = Scan::where('visitor_id', $visitorId)->count();

        if ($guestScanCount >= self::MAX_GUEST_SCANS) {
            return redirect()->route('register')
                ->with('error', 'You\'ve used all 3 free scans. Create a free account for unlimited basic scans.');
        }

        $scan = $scanService->executeGuestScan($request->validated('url'), $visitorId);
        $scanService->dispatchScan($scan);

        return redirect()->route('experiment.scan.show', $scan);
    }
}
