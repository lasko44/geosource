<?php

namespace App\Jobs;

use App\Models\CitationCheck;
use App\Services\Citation\CitationAnalyzerService;
use App\Services\Citation\CitationService;
use App\Services\Citation\Platforms\ClaudeService;
use App\Services\Citation\Platforms\GeminiService;
use App\Services\Citation\Platforms\OpenAIBrowsingService;
use App\Services\Citation\Platforms\PerplexityService;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckCitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    private const STEPS = [
        'initializing' => ['label' => 'Initializing', 'percent' => 10],
        'querying_platform' => ['label' => 'Querying Platform', 'percent' => 30],
        'analyzing_response' => ['label' => 'Analyzing Response', 'percent' => 60],
        'extracting_citations' => ['label' => 'Extracting Citations', 'percent' => 80],
        'completed' => ['label' => 'Completed', 'percent' => 100],
    ];

    public function __construct(
        public CitationCheck $check
    ) {}

    public function handle(
        CitationService $citationService,
        SubscriptionService $subscriptionService,
        PerplexityService $perplexityService,
        OpenAIBrowsingService $openAIService,
        ClaudeService $claudeService,
        GeminiService $geminiService,
        CitationAnalyzerService $analyzerService
    ): void {
        // Re-verify subscription before completing the check
        if (! $this->verifySubscriptionStillValid($subscriptionService)) {
            $this->markFailed('Your subscription has changed and this check cannot be completed.');

            return;
        }

        $this->updateProgress('initializing');

        try {
            $query = $this->check->citationQuery;

            if (! $query) {
                $this->markFailed('Citation query not found.');

                return;
            }

            // Step 2: Query the platform
            $this->updateProgress('querying_platform');

            $result = match ($this->check->platform) {
                CitationCheck::PLATFORM_PERPLEXITY => $perplexityService->check($query, $this->check),
                CitationCheck::PLATFORM_OPENAI => $openAIService->check($query, $this->check),
                CitationCheck::PLATFORM_CLAUDE => $claudeService->check($query, $this->check),
                CitationCheck::PLATFORM_GEMINI => $geminiService->check($query, $this->check),
                default => throw new \RuntimeException("Unsupported platform: {$this->check->platform}"),
            };

            // Step 3: Analyze response
            $this->updateProgress('analyzing_response');

            // Step 4: Extract citations
            $this->updateProgress('extracting_citations');

            // Update check with results
            $this->check->update([
                'status' => CitationCheck::STATUS_COMPLETED,
                'is_cited' => $result['is_cited'],
                'ai_response' => $result['ai_response'],
                'citations' => $result['citations'],
                'metadata' => $result['metadata'],
                'progress_step' => 'Completed',
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);

            // Process completion and create alerts if needed
            $citationService->processCheckCompletion($this->check);

            // Update the query's last checked timestamp
            $query->update(['last_checked_at' => now()]);

        } catch (\Exception $e) {
            $this->markFailed($e->getMessage());
        }
    }

    private function updateProgress(string $step): void
    {
        $stepData = self::STEPS[$step] ?? ['label' => $step, 'percent' => 0];

        $this->check->update([
            'status' => CitationCheck::STATUS_PROCESSING,
            'progress_step' => $stepData['label'],
            'progress_percent' => $stepData['percent'],
            'started_at' => $this->check->started_at ?? now(),
        ]);
    }

    private function markFailed(string $message): void
    {
        $this->check->update([
            'status' => CitationCheck::STATUS_FAILED,
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    /**
     * Verify the user's subscription is still valid for this check.
     */
    private function verifySubscriptionStillValid(SubscriptionService $subscriptionService): bool
    {
        $user = $this->check->user;

        if (! $user) {
            return false;
        }

        // Refresh the user model to get current subscription state
        $user->refresh();

        // Admins always pass
        if ($user->is_admin) {
            return true;
        }

        // Verify user still has citation access
        $citationLimit = $subscriptionService->getLimit($user, 'citation_checks_per_day');

        return $citationLimit !== null && $citationLimit !== 0;
    }
}
