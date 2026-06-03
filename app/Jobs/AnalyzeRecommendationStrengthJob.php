<?php

namespace App\Jobs;

use App\Models\CitationCheck;
use App\Services\Citation\RecommendationStrengthAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a single recommendation-strength analysis on a CitationCheck.
 * Reuses the check's stored ai_response — no new web search, no new
 * platform call to OpenAI/Perplexity. Just one Haiku classification call.
 *
 * Idempotent: skips checks that already have a recommendation_strength score.
 */
class AnalyzeRecommendationStrengthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 90;

    /**
     * @param  array<int, string>  $brandNames  Optional display-name variants for the brand.
     */
    public function __construct(
        public int $checkId,
        public array $brandNames = [],
    ) {}

    public function handle(RecommendationStrengthAnalyzer $analyzer): void
    {
        $check = CitationCheck::find($this->checkId);
        if (! $check) {
            return;
        }

        // Idempotency: skip if already analyzed
        if ($check->recommendation_strength !== null) {
            return;
        }

        if (empty($check->ai_response) || $check->status !== CitationCheck::STATUS_COMPLETED) {
            return;
        }

        $query = $check->citationQuery;
        $domain = $query?->domain ?? '';
        if ($domain === '') {
            return;
        }

        try {
            $result = $analyzer->analyze($check->ai_response, $domain, $this->brandNames);

            $check->update([
                'recommendation_strength' => $result['strength_score'],
                'mention_analysis' => [
                    'mention_type' => $result['mention_type'],
                    'position_rank' => $result['position_rank'],
                    'is_top_pick' => $result['is_top_pick'],
                    'top_pick_rank' => $result['top_pick_rank'],
                    'has_buy_link' => $result['has_buy_link'],
                    'reasoning' => $result['reasoning'],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Recommendation strength analysis failed', [
                'check_id' => $this->checkId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
