<?php

namespace App\Jobs\GeoStudy;

use App\Models\GeoStudy;
use App\Models\GeoStudyResult;
use App\Services\GEO\GeoScorer;
use App\Services\GeoStudy\UrlCollectorService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ProcessGeoStudyUrlJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180; // 3 minutes per URL

    public int $maxExceptions = 2;

    public function __construct(
        public GeoStudy $study,
        public GeoStudyResult $result
    ) {
        $this->onQueue('geo-study');
    }

    public function handle(GeoScorer $geoScorer, UrlCollectorService $urlCollector): void
    {
        // Check if batch was cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Check if study was cancelled
        $this->study->refresh();
        if ($this->study->isCancelled()) {
            return;
        }

        // Mark as processing
        $this->result->update(['status' => GeoStudyResult::STATUS_PROCESSING]);

        try {
            // Fetch webpage
            $html = $this->fetchWebpage($this->result->url);

            if ($html === null) {
                $this->markFailed('Failed to fetch webpage');
                return;
            }

            // Extract title
            $title = $this->extractTitle($html) ?? parse_url($this->result->url, PHP_URL_HOST);

            // Extract domain
            $domain = $urlCollector->extractDomain($this->result->url);

            // Score content using agency tier (all 12 pillars)
            $geoScorer->forTier(GeoScorer::TIER_AGENCY);
            $scoreResult = $geoScorer->score($html, ['url' => $this->result->url]);

            // Extract pillar scores
            $pillarScores = $this->extractPillarScores($scoreResult['pillars']);

            // Update result
            $this->result->update([
                'title' => $title,
                'domain' => $domain,
                'total_score' => $scoreResult['score'],
                'grade' => $scoreResult['grade'],
                'percentage' => $scoreResult['percentage'],
                'pillar_definitions' => $pillarScores['definitions'],
                'pillar_structure' => $pillarScores['structure'],
                'pillar_authority' => $pillarScores['authority'],
                'pillar_machine_readable' => $pillarScores['machine_readable'],
                'pillar_answerability' => $pillarScores['answerability'],
                'pillar_eeat' => $pillarScores['eeat'],
                'pillar_citations' => $pillarScores['citations'],
                'pillar_ai_accessibility' => $pillarScores['ai_accessibility'],
                'pillar_freshness' => $pillarScores['freshness'],
                'pillar_readability' => $pillarScores['readability'],
                'pillar_question_coverage' => $pillarScores['question_coverage'],
                'pillar_multimedia' => $pillarScores['multimedia'],
                'full_results' => $scoreResult,
                'status' => GeoStudyResult::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);

            // Update study progress
            $this->study->updateProgress();

        } catch (\Exception $e) {
            Log::error("Failed to process GEO study URL: {$this->result->url}", [
                'error' => $e->getMessage(),
                'study_id' => $this->study->id,
            ]);

            $this->markFailed($e->getMessage());
        }
    }

    /**
     * Extract pillar scores from scoring result.
     */
    private function extractPillarScores(array $pillars): array
    {
        $scores = [];

        $pillarMap = [
            'definitions' => 'definitions',
            'structure' => 'structure',
            'authority' => 'authority',
            'machine_readable' => 'machine_readable',
            'answerability' => 'answerability',
            'eeat' => 'eeat',
            'citations' => 'citations',
            'ai_accessibility' => 'ai_accessibility',
            'freshness' => 'freshness',
            'readability' => 'readability',
            'question_coverage' => 'question_coverage',
            'multimedia' => 'multimedia',
        ];

        foreach ($pillarMap as $resultKey => $pillarKey) {
            $scores[$resultKey] = $pillars[$pillarKey]['percentage'] ?? null;
        }

        return $scores;
    }

    /**
     * Fetch webpage content.
     */
    private function fetchWebpage(string $url): ?string
    {
        // Try HTTP first
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->get($url);

        if ($response->successful()) {
            return $response->body();
        }

        // If blocked, try headless browser
        $status = $response->status();
        if (in_array($status, [403, 503, 429, 406, 451])) {
            return $this->fetchWithBrowser($url);
        }

        Log::warning("HTTP request failed for {$url} with status {$status}");
        return null;
    }

    /**
     * Fetch webpage using headless browser.
     */
    private function fetchWithBrowser(string $url): ?string
    {
        try {
            $browsershot = Browsershot::url($url)
                ->setNodeBinary(config('browsershot.node_binary', '/usr/bin/node'))
                ->setNpmBinary(config('browsershot.npm_binary', '/usr/bin/npm'))
                ->noSandbox()
                ->dismissDialogs()
                ->timeout(60)
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
                ->windowSize(1920, 1080)
                ->addChromiumArguments([
                    'disable-blink-features' => 'AutomationControlled',
                ]);

            return $browsershot->bodyHtml();
        } catch (\Exception $e) {
            Log::warning("Browser fetch failed for {$url}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Extract title from HTML.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    /**
     * Mark result as failed.
     */
    private function markFailed(string $message): void
    {
        $this->result->update([
            'status' => GeoStudyResult::STATUS_FAILED,
            'error_message' => $message,
            'processed_at' => now(),
        ]);

        $this->study->updateProgress();
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("ProcessGeoStudyUrlJob failed: {$this->result->url}", [
            'error' => $exception?->getMessage(),
            'study_id' => $this->study->id,
            'result_id' => $this->result->id,
        ]);

        $this->markFailed($exception?->getMessage() ?? 'Unknown error');
    }
}
