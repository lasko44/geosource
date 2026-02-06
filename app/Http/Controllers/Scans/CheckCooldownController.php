<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scans\CheckCooldownRequest;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;

/**
 * Checks if a URL is on cooldown before allowing a rescan.
 */
class CheckCooldownController extends Controller
{
    public function __invoke(CheckCooldownRequest $request, ScanService $scanService): JsonResponse
    {
        $tier = $request->getTier();
        $cooldown = $scanService->checkCooldown($request->getUrl(), $request->user()->id, $tier);

        return response()->json($scanService->formatCooldownResponse($cooldown, $tier));
    }
}
