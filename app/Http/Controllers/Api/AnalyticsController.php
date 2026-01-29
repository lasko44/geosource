<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\PageViewTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private PageViewTracker $tracker
    ) {}

    /**
     * Mark a page view as engaged (visitor stayed on page for a few seconds).
     */
    public function markEngaged(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $path = $request->input('path', '/');

        if (!$sessionId) {
            return response()->json(['success' => false], 400);
        }

        $success = $this->tracker->markEngaged($sessionId, $path);

        return response()->json(['success' => $success]);
    }
}
