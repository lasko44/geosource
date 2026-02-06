<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checks if a URL is on cooldown before allowing a rescan.
 */
class CheckCooldownController extends Controller
{
    public function __invoke(Request $request, ScanService $scanService): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'tier' => 'nullable|in:basic,pro,full',
        ]);

        $tier = $request->input('tier', 'basic');
        $cooldown = $scanService->checkCooldown($request->url, $request->user()->id, $tier);

        return response()->json($scanService->formatCooldownResponse($cooldown, $tier));
    }
}
