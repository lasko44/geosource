<?php

namespace App\Jobs;

use App\Mail\ScheduledScanCompletedMail;
use App\Models\Scan;
use App\Models\Team;
use App\Services\CompetitorDiscoveryService;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\ContentExtractor;
use App\Services\RAG\VectorStore;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Browsershot\Browsershot;

/**
 * Performs a GEO scan on a website and calculates optimization scores.
 */
class ScanWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public int $maxExceptions = 2;

    private const STEPS = [
        'fetching' => ['label' => 'Fetching webpage', 'percent' => 10],
        'analyzing_structure' => ['label' => 'Analyzing page structure', 'percent' => 20],
        'checking_llms_txt' => ['label' => 'Checking llms.txt', 'percent' => 30],
        'scoring_pillars' => ['label' => 'Scoring GEO pillars', 'percent' => 40],
        'scoring_authority' => ['label' => 'Evaluating authority & trust', 'percent' => 50],
        'scoring_advanced' => ['label' => 'Analyzing advanced metrics', 'percent' => 60],
        'scoring_final' => ['label' => 'Finalizing pillar scores', 'percent' => 70],
        'finding_similar' => ['label' => 'Finding similar content', 'percent' => 75],
        'generating_recommendations' => ['label' => 'Generating AI recommendations', 'percent' => 85],
        'calculating_benchmarks' => ['label' => 'Calculating benchmarks', 'percent' => 95],
        'completed' => ['label' => 'Completed', 'percent' => 100],
    ];

    public function __construct(
        public Scan $scan
    ) {}

    public function handle(
        GeoScorer $geoScorer,
        EnhancedGeoScorer $enhancedGeoScorer,
        VectorStore $vectorStore,
        SubscriptionService $subscriptionService,
        ContentExtractor $contentExtractor
    ): void {
        // Check if scan was cancelled before we start
        if ($this->isCancelled()) {
            return;
        }

        // Re-verify subscription before completing the scan
        // This prevents downgraded users from completing queued scans
        if (! $this->verifySubscriptionStillValid($subscriptionService)) {
            $this->markFailed('Subscription changed during scan', 'Your subscription has changed and this scan cannot be completed. Please try again.');

            return;
        }

        $this->updateProgress('fetching');

        try {
            // Step 1: Fetch webpage - try HTTP first, fall back to headless browser
            $html = $this->fetchWebpage($this->scan->url);

            if ($html === null) {
                return; // Error already handled in fetchWebpage
            }

            // Sanitize HTML to prevent UTF-8 encoding issues
            $html = $this->sanitizeUtf8($html);

            $title = $this->extractTitle($html) ?? parse_url($this->scan->url, PHP_URL_HOST);

            // Update title early so user sees it
            $this->scan->update(['title' => $title]);

            // Step 2: Analyze structure
            $this->updateProgress('analyzing_structure');

            $teamId = $this->scan->team_id;
            $userId = $this->scan->user_id;

            // Determine user's plan tier for scoring pillars
            $tier = $this->getUserTier();

            // RAG is available when enabled and API key exists
            $ragAvailable = config('rag.geo.use_rag_analysis', false)
                && ! empty(config('rag.openai.api_key'));

            // RAG enhancement requires Pro/Full tier
            // Documents are isolated by team_id (preferred) or user_id (fallback)
            // Basic tier scans use rule-based scoring only
            $useEnhanced = $ragAvailable
                && $tier !== GeoScorer::TIER_FREE;

            // Step 3: Check llms.txt (happens inside MachineReadableScorer)
            $this->updateProgress('checking_llms_txt');

            // Check for cancellation before expensive scoring
            if ($this->isCancelled()) {
                return;
            }

            // Step 4: Score content - configure scorer for user's plan tier
            $geoScorer->forTier($tier);
            $enhancedGeoScorer->forTier($tier);

            // Progress callback for granular updates
            $progressCallback = fn (string $step) => $this->updateProgress($step);
            $this->updateProgress('scoring_pillars');

            if ($useEnhanced) {
                $result = $enhancedGeoScorer->analyze(
                    $html,
                    $teamId,
                    $userId,
                    ['url' => $this->scan->url],
                    $progressCallback
                );
            } else {
                $result = $geoScorer->score($html, ['url' => $this->scan->url]);
                $this->updateProgress('generating_recommendations');
            }

            // Sanitize results to prevent UTF-8 encoding errors
            $sanitizedResult = $this->sanitizeUtf8($result);

            $this->scan->update([
                'title' => $this->sanitizeUtf8($title),
                'score' => Arr::get($sanitizedResult, 'score'),
                'grade' => Arr::get($sanitizedResult, 'grade'),
                'results' => $sanitizedResult,
                'status' => 'completed',
                'progress_step' => 'completed',
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);

            // Note: Tokens are now deducted upfront in the controller
            // No deduction needed here

            // Send email notification if this is a scheduled scan
            $this->sendScheduledScanNotification();

            // Store in vector database for Pro/Full scans (builds knowledge base for benchmarking)
            if ($useEnhanced) {
                try {
                    // Extract main content for better vector storage
                    $contentForStorage = $html;
                    if (config('rag.extraction.enabled', true)) {
                        $contentForStorage = $contentExtractor->extract($html);
                    }

                    $vectorStore->addDocument(
                        $teamId,
                        $userId,
                        $title,
                        $contentForStorage,
                        [
                            'type' => 'scanned_page',
                            'url' => $this->scan->url,
                            'scan_id' => $this->scan->id,
                            'geo_score' => Arr::get($result, 'score'),
                            'is_competitor' => $this->scan->is_competitor,
                        ],
                        chunk: true
                    );
                } catch (\Exception $e) {
                    logger()->warning('Failed to store scan in vector DB: '.$e->getMessage());
                }
            }

            // Discover and scan competitors if auto-find is enabled
            if ($this->scan->auto_find_competitors) {
                $this->discoverCompetitors();
            }

            // Check if this is a competitor scan and update parent status
            $this->checkAndUpdateParentCompetitorStatus();
        } catch (\Exception $e) {
            $this->markFailed('Exception during scan: '.$e->getMessage());
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

    private function markFailed(string $internalMessage, ?string $userMessage = null): void
    {
        // Generic user-facing message
        $userMessage = $userMessage ?? 'We were unable to scan this URL. Please try again or contact support.';

        $this->scan->update([
            'status' => 'failed',
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => $userMessage,
            'internal_error' => $internalMessage, // Detailed error for Nova
            'completed_at' => now(),
        ]);

        // Refund tokens if they were charged for this scan
        $this->refundTokensForFailedScan();

        // Log detailed error
        Log::error('Scan failed', [
            'scan_id' => $this->scan->id,
            'scan_uuid' => $this->scan->uuid,
            'url' => $this->scan->url,
            'user_id' => $this->scan->user_id,
            'error' => $internalMessage,
        ]);

        // Email notification to admin
        $this->notifyAdminOfFailure($internalMessage);

        // Check if this is a competitor scan and update parent status
        $this->checkAndUpdateParentCompetitorStatus();
    }

    /**
     * Check if all sibling competitor scans are done and update the parent scan status.
     */
    private function checkAndUpdateParentCompetitorStatus(): void
    {
        // Only relevant for competitor scans
        if (! $this->scan->is_competitor || ! $this->scan->parent_scan_id) {
            return;
        }

        $parentScan = Scan::find($this->scan->parent_scan_id);
        if (! $parentScan) {
            return;
        }

        // Check if all sibling competitor scans are done (completed or failed)
        $pendingCompetitors = Scan::where('parent_scan_id', $parentScan->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        if ($pendingCompetitors === 0) {
            // All competitor scans are done - get completed ones
            $completedCompetitors = Scan::where('parent_scan_id', $parentScan->id)
                ->where('status', 'completed')
                ->whereNotNull('score')
                ->get();

            $completedCount = $completedCompetitors->count();

            // Calculate competitor benchmark
            $competitorBenchmark = $this->calculateCompetitorBenchmark($parentScan, $completedCompetitors);

            // Update parent scan results with competitor benchmark
            $results = $parentScan->results ?? [];
            $results['competitor_benchmark'] = $competitorBenchmark;

            $parentScan->update([
                'competitor_discovery_status' => 'completed',
                'competitors_found' => $completedCount,
                'results' => $results,
            ]);

            Log::info('All competitor scans completed', [
                'parent_scan_id' => $parentScan->id,
                'completed_count' => $completedCount,
                'benchmark' => $competitorBenchmark,
            ]);
        }
    }

    /**
     * Calculate competitor benchmark for the parent scan.
     */
    private function calculateCompetitorBenchmark(Scan $parentScan, \Illuminate\Support\Collection $competitors): array
    {
        if ($competitors->isEmpty()) {
            return [
                'position' => 'no_competitors',
                'percentile' => null,
                'avg_competitor_score' => null,
                'score_difference' => null,
                'competitors_analyzed' => 0,
                'comparison' => 'No competitor scans completed for comparison.',
            ];
        }

        $scores = $competitors->pluck('score')->filter()->values()->toArray();
        if (empty($scores)) {
            return [
                'position' => 'no_competitors',
                'percentile' => null,
                'avg_competitor_score' => null,
                'score_difference' => null,
                'competitors_analyzed' => 0,
                'comparison' => 'No competitor scores available for comparison.',
            ];
        }

        $avgScore = array_sum($scores) / count($scores);
        $currentScore = $parentScan->score ?? 0;
        $scoreDiff = $currentScore - $avgScore;

        // Calculate percentile (how many competitors you beat)
        $belowCount = count(array_filter($scores, fn ($s) => $s < $currentScore));
        $percentile = round(($belowCount / count($scores)) * 100);

        $position = match (true) {
            $scoreDiff >= 15 => 'dominant',
            $scoreDiff >= 5 => 'ahead',
            $scoreDiff >= -5 => 'competitive',
            $scoreDiff >= -15 => 'behind',
            default => 'far_behind',
        };

        $comparison = match ($position) {
            'dominant' => "Outstanding! You outperform {$percentile}% of competitors by ".abs(round($scoreDiff, 1)).' points.',
            'ahead' => "Strong position. You're ".abs(round($scoreDiff, 1))." points ahead of your competitors' average.",
            'competitive' => "You're competitive with your rivals. Focus on key differentiators.",
            'behind' => "You're ".abs(round($scoreDiff, 1)).' points behind competitors. Review their strategies for insights.',
            'far_behind' => "Significant gap to close. You're ".abs(round($scoreDiff, 1)).' points behind the competition.',
            default => 'Unable to determine competitive position.',
        };

        return [
            'position' => $position,
            'percentile' => $percentile,
            'avg_competitor_score' => round($avgScore, 1),
            'score_difference' => round($scoreDiff, 1),
            'competitors_analyzed' => count($scores),
            'comparison' => $comparison,
        ];
    }

    private function notifyAdminOfFailure(string $error): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Scan Failed\n\n".
                "URL: {$this->scan->url}\n".
                "Scan ID: {$this->scan->id}\n".
                "UUID: {$this->scan->uuid}\n".
                "User ID: {$this->scan->user_id}\n".
                "Error: {$error}\n".
                'Time: '.now()->toDateTimeString(),
                function ($message) {
                    $message->to('matt@geosource.ai')
                        ->subject('GeoSource Scan Failed: '.parse_url($this->scan->url, PHP_URL_HOST));
                }
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send scan failure notification email: '.$e->getMessage());
        }
    }

    /**
     * Check if the scan has been cancelled.
     */
    private function isCancelled(): bool
    {
        $this->scan->refresh();

        return $this->scan->status === 'cancelled';
    }

    /**
     * Fetch webpage content, with headless browser fallback for protected sites.
     */
    private function fetchWebpage(string $url): ?string
    {
        try {
            // First, try simple HTTP request (fast)
            // Note: Only accept gzip/deflate - brotli (br) causes issues with some curl versions
            $response = Http::timeout(30)
                ->connectTimeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Cache-Control' => 'no-cache',
                    'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Mode' => 'navigate',
                    'Sec-Fetch-Site' => 'none',
                    'Sec-Fetch-User' => '?1',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->get($url);

            // If successful, return the HTML
            if ($response->successful()) {
                return $response->body();
            }

            // If blocked or bot-protected, try headless browser
            $status = $response->status();
            if (in_array($status, [403, 406, 409, 429, 451, 503])) {
                Log::info("HTTP request blocked ({$status}) for {$url}, trying headless browser");

                return $this->fetchWithBrowser($url);
            }

            // For other errors (404, 500, etc.), fail immediately
            $userMessage = match ($status) {
                404 => 'The page was not found. Please check the URL is correct.',
                500, 502, 503, 504 => 'The website is experiencing issues. Please try again later.',
                default => null,
            };
            $this->markFailed("HTTP request failed with status {$status}", $userMessage);

            return null;
        } catch (ConnectionException $e) {
            // Handle connection errors (timeout, DNS failure, SSL issues)
            Log::warning("HTTP connection error for {$url}: ".$e->getMessage().', trying headless browser');

            return $this->fetchWithBrowser($url);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle request errors (HTTP 4xx/5xx responses)
            Log::warning("HTTP request error for {$url}: ".$e->getMessage().', trying headless browser');

            return $this->fetchWithBrowser($url);
        } catch (\Exception $e) {
            // Handle any other errors (curl errors, encoding issues, etc.)
            $message = $e->getMessage();

            // Check if it's a curl error that we can retry with browser
            if (str_contains($message, 'cURL error') || str_contains($message, 'curl')) {
                Log::warning("cURL error for {$url}: {$message}, trying headless browser");

                return $this->fetchWithBrowser($url);
            }

            // For other exceptions, re-throw to be handled by the job's main try/catch
            throw $e;
        }
    }

    /**
     * Fetch webpage using headless browser (Puppeteer via Browsershot).
     * Includes stealth options to bypass bot detection.
     */
    private function fetchWithBrowser(string $url): ?string
    {
        try {
            $browsershot = Browsershot::url($url)
                ->setNodeBinary(config('browsershot.node_binary', '/usr/bin/node'))
                ->setNpmBinary(config('browsershot.npm_binary', '/usr/bin/npm'))
                ->noSandbox()
                ->dismissDialogs()
                ->timeout(90)
                // Stealth options to bypass bot detection
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
                ->windowSize(1920, 1080)
                ->addChromiumArguments([
                    'disable-blink-features' => 'AutomationControlled',
                    'disable-features' => 'IsolateOrigins,site-per-process,TranslateUI',
                    'disable-site-isolation-trials',
                    'disable-dev-shm-usage',
                    'disable-gpu',
                    'no-first-run',
                    'no-default-browser-check',
                    'disable-infobars',
                    'disable-extensions',
                    'disable-popup-blocking',
                    'disable-background-networking',
                    'disable-sync',
                    'metrics-recording-only',
                    'disable-default-apps',
                    'mute-audio',
                    'no-zygote',
                    'single-process',
                    'disable-hang-monitor',
                    'disable-prompt-on-repost',
                    'disable-client-side-phishing-detection',
                ])
                ->setExtraHttpHeaders([
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Sec-Ch-Ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Mode' => 'navigate',
                    'Sec-Fetch-Site' => 'none',
                    'Sec-Fetch-User' => '?1',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                // Wait for page to fully load
                ->setDelay(5000);

            // Use custom Chrome path if specified
            if ($chromePath = config('browsershot.chrome_path')) {
                $browsershot->setChromePath($chromePath);
            }

            $html = $browsershot->bodyHtml();

            if (empty($html)) {
                $this->markFailed('Browsershot returned empty response for '.$url);

                return null;
            }

            // Check if we got a Cloudflare challenge page or access denied
            if (str_contains($html, 'challenge-platform') || str_contains($html, 'cf-browser-verification') || str_contains($html, 'Access denied') || str_contains($html, 'Just a moment')) {
                Log::warning("Bot protection detected for {$url}, waiting longer...");

                // Try again with longer delay
                $html = $browsershot->setDelay(10000)->bodyHtml();

                // If still blocked, fail with helpful message
                if (str_contains($html, 'challenge-platform') || str_contains($html, 'Access denied') || str_contains($html, 'Just a moment')) {
                    $this->markFailed('Bot protection (Cloudflare/etc) blocked scan after retry for '.$url);

                    return null;
                }
            }

            return $html;
        } catch (\Exception $e) {
            Log::error("Browsershot failed for {$url}: ".$e->getMessage());
            $this->markFailed('Browsershot exception: '.$e->getMessage());

            return null;
        }
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
     * Recursively sanitize UTF-8 strings in an array to prevent JSON encoding errors.
     */
    /**
     * Recursively sanitize UTF-8 strings to prevent JSON encoding errors.
     * Handles various character encoding issues from web scraping.
     */
    private function sanitizeUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            // Detect source encoding and convert to UTF-8
            $encoding = mb_detect_encoding($data, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $data = mb_convert_encoding($data, 'UTF-8', $encoding);
            }

            // Remove BOM if present
            $data = preg_replace('/^\xEF\xBB\xBF/', '', $data);

            // Remove null bytes and other control characters (except newlines/tabs)
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);

            // Replace invalid UTF-8 sequences with empty string
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

            // Final pass with iconv to strip anything still invalid
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $data);

            // If iconv failed completely, try aggressive cleanup
            if ($clean === false || $clean === '') {
                $clean = preg_replace('/[^\x20-\x7E\x0A\x0D\x09]/', '', $data);
            }

            return $clean ?: '';
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $cleanKey = is_string($key) ? $this->sanitizeUtf8($key) : $key;
                $result[$cleanKey] = $this->sanitizeUtf8($value);
            }
            return $result;
        }

        return $data;
    }

    /**
     * Determine the user's tier for GEO scoring pillars.
     * Uses the scan's requested_tier, which was validated at scan creation time.
     */
    private function getUserTier(): string
    {
        // Use the requested tier from the scan (validated at creation)
        $requestedTier = $this->scan->requested_tier ?? 'basic';

        return match ($requestedTier) {
            'full' => GeoScorer::TIER_AGENCY,
            'pro' => GeoScorer::TIER_PRO,
            default => GeoScorer::TIER_FREE,
        };
    }

    /**
     * Send email notification for scheduled scan completion.
     */
    private function sendScheduledScanNotification(): void
    {
        // Only send notification if this is a scheduled scan
        if (! $this->scan->scheduled_scan_id) {
            return;
        }

        // Load the scheduled scan with relationships
        $scheduledScan = $this->scan->scheduledScan;
        if (! $scheduledScan) {
            return;
        }

        // Refresh the scan to ensure we have the latest data
        $this->scan->refresh();
        $this->scan->load(['user', 'team']);

        try {
            // Collect all recipients (avoid duplicates)
            $recipients = collect();

            // Always add the scan creator
            if ($this->scan->user) {
                $recipients->push($this->scan->user);
            }

            // If this is a team scan, add team owner and members
            if ($this->scan->team_id && $this->scan->team) {
                $team = $this->scan->team;

                // Add team owner if different from scan creator
                if ($team->owner && $team->owner->id !== $this->scan->user_id) {
                    $recipients->push($team->owner);
                }

                // Add all team members
                foreach ($team->members as $member) {
                    if (! $recipients->contains('id', $member->id)) {
                        $recipients->push($member);
                    }
                }
            }

            // Send email to each unique recipient
            // Using send() instead of queue() since we're already in a queued job
            // and queue() causes serialization issues with typed model properties
            foreach ($recipients->unique('id') as $recipient) {
                Mail::to($recipient->email)->send(
                    new ScheduledScanCompletedMail($this->scan, $scheduledScan, $recipient)
                );
            }

            logger()->info('Scheduled scan notification sent', [
                'scan_id' => $this->scan->id,
                'scheduled_scan_id' => $scheduledScan->id,
                'recipients_count' => $recipients->unique('id')->count(),
            ]);
        } catch (\Exception $e) {
            // Log but don't fail the scan if notification fails
            logger()->warning('Failed to send scheduled scan notification: '.$e->getMessage(), [
                'scan_id' => $this->scan->id,
                'scheduled_scan_id' => $scheduledScan->id ?? null,
            ]);
        }
    }

    /**
     * Verify the user's subscription is still valid for this scan.
     *
     * This prevents users who downgrade their subscription while scans
     * are queued from completing scans they no longer have quota for.
     */
    private function verifySubscriptionStillValid(SubscriptionService $subscriptionService): bool
    {
        $user = $this->scan->user;

        if (! $user) {
            // Guest scans (visitor_id set, no user) are always valid for basic tier
            return $this->scan->visitor_id !== null;
        }

        // Refresh the user model to get current subscription state
        $user->refresh();

        // Admins always pass
        if ($user->is_admin) {
            return true;
        }

        $teamId = $this->scan->team_id;

        if ($teamId) {
            // For team scans, verify the team still exists and owner has valid subscription
            $team = Team::find($teamId);
            if (! $team) {
                return false;
            }

            // Verify user still has access to the team
            if (! $user->allTeams()->contains('id', $teamId)) {
                return false;
            }

            // Verify team owner still has quota (re-check at execution time)
            return $subscriptionService->canScanForTeam($team);
        }

        // For personal scans, verify user still has quota
        return $subscriptionService->canScan($user);
    }

    /**
     * Refund tokens for a failed scan.
     * Only refunds if tokens were charged for this scan.
     */
    private function refundTokensForFailedScan(): void
    {
        // Only refund if tokens were actually charged
        if (! $this->scan->tokens_charged || ! $this->scan->tokens_amount || $this->scan->tokens_amount <= 0) {
            return;
        }

        $user = $this->scan->user;
        if (! $user || $user->is_admin) {
            return;
        }

        try {
            $tokenService = app(TokenService::class);
            $amount = $this->scan->tokens_amount;

            $tokenService->refund(
                $user,
                $amount,
                "Refund for failed {$this->scan->requested_tier} scan"
            );

            // Mark scan as refunded
            $this->scan->update([
                'tokens_refunded' => true,
            ]);

            Log::info('Tokens refunded for failed scan', [
                'user_id' => $user->id,
                'scan_id' => $this->scan->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refund tokens for scan', [
                'user_id' => $user->id,
                'scan_id' => $this->scan->id,
                'amount' => $this->scan->tokens_amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Discover and scan competitor websites using AI.
     */
    private function discoverCompetitors(): void
    {
        try {
            $competitorService = app(CompetitorDiscoveryService::class);
            $discoveredScans = $competitorService->discoverAndScanCompetitors($this->scan);

            if (! empty($discoveredScans)) {
                Log::info('Competitor discovery completed', [
                    'parent_scan_id' => $this->scan->id,
                    'competitors_found' => count($discoveredScans),
                ]);
            }
        } catch (\Exception $e) {
            // Log but don't fail the main scan if competitor discovery fails
            Log::warning('Failed to discover competitors', [
                'scan_id' => $this->scan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $internalMessage = $exception?->getMessage() ?? 'Job failed after maximum attempts';

        $this->scan->update([
            'status' => 'failed',
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => 'We were unable to scan this URL. Please try again or contact support.',
            'internal_error' => 'Job failure: '.$internalMessage,
            'completed_at' => now(),
        ]);

        // Refund tokens if they were charged for this scan
        $this->refundTokensForFailedScan();

        Log::error('Scan job failed', [
            'scan_id' => $this->scan->id,
            'url' => $this->scan->url,
            'error' => $internalMessage,
        ]);

        // Email notification to admin
        $this->notifyAdminOfFailure('Job failure: '.$internalMessage);
    }
}
