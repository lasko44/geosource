<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\PageViewTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Marks page views as engaged when visitors stay on the page.
 */
class AnalyticsController extends Controller
{
    /**
     * Mark a page view as engaged (visitor stayed on page for a few seconds).
     */
    public function __invoke(Request $request, PageViewTracker $tracker): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $path = $request->input('path', '/');

        if (! $sessionId) {
            return response()->json(['success' => false], 400);
        }

        $success = $tracker->markEngaged($sessionId, $path);

        return response()->json(['success' => $success]);
    }
}
