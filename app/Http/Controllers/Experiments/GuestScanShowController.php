<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Displays scan results for guest visitors, verified by visitor cookie.
 */
class GuestScanShowController extends Controller
{
    public function __invoke(Scan $scan, Request $request, ScanService $scanService): Response
    {
        $visitorId = $request->cookie('ab_visitor');

        // Guest can only view their own scans
        if (! $visitorId || $scan->visitor_id !== $visitorId) {
            throw new NotFoundHttpException;
        }

        $scan->load('discoveredCompetitors');

        $scanData = $scan->toArray();

        // Guest scans are basic tier — filter pillars to basic set
        if (isset($scanData['results']['pillars'])) {
            $scanData['results']['pillars'] = array_filter(
                $scanData['results']['pillars'],
                fn ($pillar) => ($pillar['tier'] ?? 'free') === 'free'
            );
        }

        $remainingScans = max(0, 3 - Scan::where('visitor_id', $visitorId)->count());

        return Inertia::render('Scans/Show', [
            'scan' => $scanData,
            'usage' => null,
            'canExportPdf' => false,
            'canEmailReport' => false,
            'cooldown' => null,
            'discoveredCompetitors' => [],
            'parentScan' => null,
            'isGuest' => true,
            'remainingGuestScans' => $remainingScans,
            'statusUrl' => "/experiment/scans/{$scan->uuid}/status",
        ]);
    }
}
