<?php

namespace App\Http\Controllers\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citations\MarkCitationAlertsReadRequest;
use App\Services\Citation\CitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitationAlertController extends Controller
{
    /**
     * List alerts.
     */
    public function index(Request $request, CitationService $citationService): JsonResponse
    {
        $user = $request->user();

        if (! $citationService->canAccessCitations($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $team = $citationService->getCurrentTeam($user);
        $alerts = $citationService->getPaginatedAlerts($user, $team);

        return response()->json($alerts);
    }

    /**
     * Mark alerts as read.
     */
    public function markRead(MarkCitationAlertsReadRequest $request, CitationService $citationService)
    {
        $citationService->markAlertsAsRead(
            $request->alert_ids,
            $request->user()
        );

        return back();
    }
}
