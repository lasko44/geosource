<?php

namespace App\Http\Controllers\Experiments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Experiments\GuestCitationCheckRequest;
use App\Jobs\CheckCitationJob;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use Illuminate\Http\RedirectResponse;

/**
 * Runs a free guest citation check across Perplexity, ChatGPT, and Claude.
 * Limited to 2 checks per visitor via cookie-based tracking.
 */
class GuestCitationCheckController extends Controller
{
    private const MAX_GUEST_CHECKS = 2;

    private const GUEST_PLATFORMS = ['perplexity', 'openai', 'claude'];

    public function __invoke(GuestCitationCheckRequest $request): RedirectResponse
    {
        $visitorId = $request->cookie('ab_visitor');

        if (! $visitorId) {
            return redirect()->route('register');
        }

        $guestCheckCount = CitationQuery::where('visitor_id', $visitorId)->count();

        if ($guestCheckCount >= self::MAX_GUEST_CHECKS) {
            return redirect()->route('register')
                ->with('error', 'You\'ve used both free citation checks. Create a free account to keep tracking.');
        }

        $query = CitationQuery::create([
            'visitor_id' => $visitorId,
            'query' => $request->validated('query'),
            'domain' => $request->validated('domain'),
            'frequency' => 'manual',
            'is_active' => false,
        ]);

        foreach (self::GUEST_PLATFORMS as $index => $platform) {
            $check = CitationCheck::create([
                'citation_query_id' => $query->id,
                'visitor_id' => $visitorId,
                'platform' => $platform,
                'status' => CitationCheck::STATUS_PENDING,
            ]);

            CheckCitationJob::dispatch($check)->delay(now()->addSeconds($index * 5));
        }

        return redirect()->route('experiment.citation.show', $query);
    }
}
