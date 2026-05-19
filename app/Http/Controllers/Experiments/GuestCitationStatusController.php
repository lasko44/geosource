<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Models\CitationQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns citation check status for guest polling, verified by visitor cookie.
 */
class GuestCitationStatusController extends Controller
{
    public function __invoke(CitationQuery $citationQuery, Request $request): JsonResponse
    {
        $visitorId = $request->cookie('ab_visitor');

        if (! $visitorId || $citationQuery->visitor_id !== $visitorId) {
            throw new NotFoundHttpException;
        }

        $checks = $citationQuery->checks()
            ->select(['id', 'platform', 'status', 'is_cited', 'ai_response', 'citations', 'error_message', 'completed_at'])
            ->get();

        return response()->json([
            'checks' => $checks,
            'all_complete' => $checks->every(fn ($c) => in_array($c->status, ['completed', 'failed'])),
        ]);
    }
}
