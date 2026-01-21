<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\VectorStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ScanWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    private const STEPS = [
        'fetching' => ['label' => 'Fetching webpage', 'percent' => 10],
        'analyzing_structure' => ['label' => 'Analyzing page structure', 'percent' => 30],
        'checking_llms_txt' => ['label' => 'Checking llms.txt', 'percent' => 50],
        'scoring_content' => ['label' => 'Scoring content', 'percent' => 70],
        'generating_recommendations' => ['label' => 'Generating recommendations', 'percent' => 90],
        'completed' => ['label' => 'Completed', 'percent' => 100],
    ];

    public function __construct(
        public Scan $scan
    ) {}

    public function handle(
        GeoScorer $geoScorer,
        EnhancedGeoScorer $enhancedGeoScorer,
        VectorStore $vectorStore
    ): void {
        $this->updateProgress('fetching');

        try {
            // Step 1: Fetch webpage
            $response = Http::timeout(60)
                ->withHeaders([
                    'User-Agent' => 'GeoSource Scanner/1.0',
                ])
                ->get($this->scan->url);

            if (! $response->successful()) {
                $this->markFailed('Failed to fetch URL. Status: '.$response->status());

                return;
            }

            $html = $response->body();
            $title = $this->extractTitle($html) ?? parse_url($this->scan->url, PHP_URL_HOST);

            // Update title early so user sees it
            $this->scan->update(['title' => $title]);

            // Step 2: Analyze structure
            $this->updateProgress('analyzing_structure');

            $useEnhanced = config('rag.geo.use_rag_analysis', false) && ! empty(config('rag.openai.api_key'));
            $teamId = $this->scan->team_id;

            // Determine user's plan tier for scoring pillars
            $tier = $this->getUserTier();

            // Step 3: Check llms.txt (happens inside MachineReadableScorer)
            $this->updateProgress('checking_llms_txt');

            // Step 4: Score content
            $this->updateProgress('scoring_content');

            // Configure scorer for user's plan tier
            $geoScorer->forTier($tier);

            if ($useEnhanced && $teamId) {
                $result = $enhancedGeoScorer->analyze($html, $teamId, ['url' => $this->scan->url]);
            } else {
                $result = $geoScorer->score($html, ['url' => $this->scan->url]);
            }

            // Step 5: Generate recommendations
            $this->updateProgress('generating_recommendations');

            $this->scan->update([
                'title' => $title,
                'score' => $result['score'],
                'grade' => $result['grade'],
                'results' => $result,
                'status' => 'completed',
                'progress_step' => 'completed',
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);

            // Optional: Store in vector database
            if ($teamId && config('rag.geo.use_rag_analysis', false)) {
                try {
                    $vectorStore->addDocument(
                        $teamId,
                        $title,
                        $html,
                        [
                            'type' => 'scanned_page',
                            'url' => $this->scan->url,
                            'scan_id' => $this->scan->id,
                            'geo_score' => $result['score'],
                        ],
                        chunk: true
                    );
                } catch (\Exception $e) {
                    logger()->warning('Failed to store scan in vector DB: '.$e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->markFailed($e->getMessage());
        }
    }

    private function updateProgress(string $step): void
    {
        $stepData = self::STEPS[$step] ?? ['label' => $step, 'percent' => 0];

        $this->scan->update([
            'status' => 'processing',
            'progress_step' => $stepData['label'],
            'progress_percent' => $stepData['percent'],
            'started_at' => $this->scan->started_at ?? now(),
        ]);
    }

    private function markFailed(string $message): void
    {
        $this->scan->update([
            'status' => 'failed',
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return trim(strip_tags($match[1]));
        }

        return null;
    }

    /**
     * Determine the user's tier for GEO scoring pillars.
     */
    private function getUserTier(): string
    {
        $user = $this->scan->user;

        if (! $user) {
            return GeoScorer::TIER_FREE;
        }

        $planKey = $user->getPlanKey();

        return match ($planKey) {
            'agency', 'agency_member', 'admin' => GeoScorer::TIER_AGENCY,
            'pro' => GeoScorer::TIER_PRO,
            default => GeoScorer::TIER_FREE,
        };
    }
}
