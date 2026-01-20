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

    public function __construct(
        public Scan $scan
    ) {}

    public function handle(
        GeoScorer $geoScorer,
        EnhancedGeoScorer $enhancedGeoScorer,
        VectorStore $vectorStore
    ): void {
        $this->scan->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
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

            $useEnhanced = config('rag.geo.use_rag_analysis', false) && ! empty(config('rag.openai.api_key'));
            $teamId = $this->scan->team_id;

            if ($useEnhanced && $teamId) {
                $result = $enhancedGeoScorer->analyze($html, $teamId, ['url' => $this->scan->url]);
            } else {
                $result = $geoScorer->score($html, ['url' => $this->scan->url]);
            }

            $this->scan->update([
                'title' => $title,
                'score' => $result['score'],
                'grade' => $result['grade'],
                'results' => $result,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

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

    private function markFailed(string $message): void
    {
        $this->scan->update([
            'status' => 'failed',
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
}
