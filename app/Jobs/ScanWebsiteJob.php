<?php

namespace App\Jobs;

use App\Mail\ScheduledScanCompletedMail;
use App\Models\Scan;
use App\Models\Team;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\VectorStore;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Browsershot\Browsershot;

class ScanWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public int $maxExceptions = 2;

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
        VectorStore $vectorStore,
        SubscriptionService $subscriptionService
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

            // Check for cancellation before expensive scoring
            if ($this->isCancelled()) {
                return;
            }

            // Step 4: Score content
            $this->updateProgress('scoring_content');

            // Configure scorer for user's plan tier
            $geoScorer->forTier($tier);
            $enhancedGeoScorer->forTier($tier);

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

            // Send email notification if this is a scheduled scan
            $this->sendScheduledScanNotification();

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
            $this->markFailed('Exception during scan: ' . $e->getMessage());
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
    }

    private function notifyAdminOfFailure(string $error): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Scan Failed\n\n" .
                "URL: {$this->scan->url}\n" .
                "Scan ID: {$this->scan->id}\n" .
                "UUID: {$this->scan->uuid}\n" .
                "User ID: {$this->scan->user_id}\n" .
                "Error: {$error}\n" .
                "Time: " . now()->toDateTimeString(),
                function ($message) {
                    $message->to('matt@geosource.ai')
                        ->subject('GeoSource Scan Failed: ' . parse_url($this->scan->url, PHP_URL_HOST));
                }
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send scan failure notification email: ' . $e->getMessage());
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
        // First, try simple HTTP request (fast)
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
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

        // If blocked (403, 503) or other issues, try headless browser
        $status = $response->status();
        if (in_array($status, [403, 503, 429, 406, 451])) {
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
                $this->markFailed('Browsershot returned empty response for ' . $url);

                return null;
            }

            // Check if we got a Cloudflare challenge page or access denied
            if (str_contains($html, 'challenge-platform') || str_contains($html, 'cf-browser-verification') || str_contains($html, 'Access denied') || str_contains($html, 'Just a moment')) {
                Log::warning("Bot protection detected for {$url}, waiting longer...");

                // Try again with longer delay
                $html = $browsershot->setDelay(10000)->bodyHtml();

                // If still blocked, fail with helpful message
                if (str_contains($html, 'challenge-platform') || str_contains($html, 'Access denied') || str_contains($html, 'Just a moment')) {
                    $this->markFailed('Bot protection (Cloudflare/etc) blocked scan after retry for ' . $url);

                    return null;
                }
            }

            return $html;
        } catch (\Exception $e) {
            Log::error("Browsershot failed for {$url}: ".$e->getMessage());
            $this->markFailed('Browsershot exception: ' . $e->getMessage());

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
            return false;
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
            'internal_error' => 'Job failure: ' . $internalMessage,
            'completed_at' => now(),
        ]);

        Log::error('Scan job failed', [
            'scan_id' => $this->scan->id,
            'url' => $this->scan->url,
            'error' => $internalMessage,
        ]);

        // Email notification to admin
        $this->notifyAdminOfFailure('Job failure: ' . $internalMessage);
    }
}
