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
use Illuminate\Support\Facades\Mail;

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
            $this->markFailed('Your subscription has changed and this scan cannot be completed. Please try again.');

            return;
        }

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

    /**
     * Check if the scan has been cancelled.
     */
    private function isCancelled(): bool
    {
        $this->scan->refresh();

        return $this->scan->status === 'cancelled';
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
}
