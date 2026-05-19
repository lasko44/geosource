<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Models\CitationQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Displays citation check results for guest visitors, verified by visitor cookie.
 */
class GuestCitationShowController extends Controller
{
    public function __invoke(CitationQuery $citationQuery, Request $request): Response
    {
        $visitorId = $request->cookie('ab_visitor');

        if (! $visitorId || $citationQuery->visitor_id !== $visitorId) {
            throw new NotFoundHttpException;
        }

        $checks = $citationQuery->checks()
            ->select(['id', 'citation_query_id', 'platform', 'status', 'is_cited', 'ai_response', 'citations', 'error_message', 'completed_at'])
            ->get();

        $remainingChecks = max(0, 2 - CitationQuery::where('visitor_id', $visitorId)->count());

        return Inertia::render('Experiments/CitationResults', [
            'query' => [
                'uuid' => $citationQuery->uuid,
                'query' => $citationQuery->query,
                'domain' => $citationQuery->domain,
            ],
            'checks' => $checks,
            'remainingChecks' => $remainingChecks,
            'statusUrl' => "/experiment/citations/{$citationQuery->uuid}/status",
        ]);
    }
}
