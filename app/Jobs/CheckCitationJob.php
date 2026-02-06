<?php

namespace App\Jobs;

use App\Models\CitationCheck;
use App\Services\Citation\CitationAnalyzerService;
use App\Services\Citation\CitationService;
use App\Services\Citation\Platforms\ClaudeService;
use App\Services\Citation\Platforms\DeepSeekService;
use App\Services\Citation\Platforms\FacebookService;
use App\Services\Citation\Platforms\GeminiService;
use App\Services\Citation\Platforms\GoogleSearchService;
use App\Services\Citation\Platforms\OpenAIBrowsingService;
use App\Services\Citation\Platforms\PerplexityService;
use App\Services\Citation\Platforms\YouTubeService;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Performs a citation check on an AI platform for a given query.
 */
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
        TokenService $tokenService,
        PerplexityService $perplexityService,
        OpenAIBrowsingService $openAIService,
        ClaudeService $claudeService,
        GeminiService $geminiService,
        DeepSeekService $deepSeekService,
        GoogleSearchService $googleSearchService,
        YouTubeService $youTubeService,
        FacebookService $facebookService,
        CitationAnalyzerService $analyzerService
    ): void {
        // Re-verify subscription before completing the check
        if (! $this->verifySubscriptionStillValid($subscriptionService)) {
            $this->markFailed('Your subscription has changed and this check cannot be completed.', $tokenService);

            return;
        }

        $this->updateProgress('initializing');

        try {
            $query = $this->check->citationQuery;

            if (! $query) {
                $this->markFailed('Citation query not found.', $tokenService);

                return;
            }

            // Step 2: Query the platform
            $this->updateProgress('querying_platform');

            $result = match ($this->check->platform) {
                // AI Platforms
                CitationCheck::PLATFORM_PERPLEXITY => $perplexityService->check($query, $this->check),
                CitationCheck::PLATFORM_OPENAI => $openAIService->check($query, $this->check),
                CitationCheck::PLATFORM_CLAUDE => $claudeService->check($query, $this->check),
                CitationCheck::PLATFORM_GEMINI => $geminiService->check($query, $this->check),
                CitationCheck::PLATFORM_DEEPSEEK => $deepSeekService->check($query, $this->check),
                // Search/Social Platforms
                CitationCheck::PLATFORM_GOOGLE => $googleSearchService->check($query, $this->check),
                CitationCheck::PLATFORM_YOUTUBE => $youTubeService->check($query, $this->check),
                CitationCheck::PLATFORM_FACEBOOK => $facebookService->check($query, $this->check),
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
            $this->markFailed($e->getMessage(), $tokenService);
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

    private function markFailed(string $message, TokenService $tokenService): void
    {
        $this->check->update([
            'status' => CitationCheck::STATUS_FAILED,
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => $message,
            'completed_at' => now(),
        ]);

        // Refund tokens for the failed check
        $this->refundTokens($tokenService);
    }

    /**
     * Refund tokens for a failed citation check.
     */
    private function refundTokens(TokenService $tokenService): void
    {
        $user = $this->check->user;

        if (! $user || $user->is_admin) {
            return;
        }

        // Get the token cost for this platform
        $providerKey = config("tokens.citation_providers.{$this->check->platform}");
        if (! $providerKey) {
            return;
        }

        $cost = config("tokens.costs.{$providerKey}", 0);
        if ($cost <= 0) {
            return;
        }

        // Refund the tokens
        $tokenService->refund(
            $user,
            $cost,
            "Refund for failed {$this->check->platform} citation check"
        );
    }

    /**
     * Verify the user can still run this check.
     * Since tokens are deducted before the job runs, we just need to ensure the user exists.
     */
    private function verifySubscriptionStillValid(SubscriptionService $subscriptionService): bool
    {
        $user = $this->check->user;

        if (! $user) {
            return false;
        }

        // Refresh the user model
        $user->refresh();

        // Admins always pass
        if ($user->is_admin) {
            return true;
        }

        // Token-based: tokens have already been deducted at this point,
        // so the check is already paid for. Just verify user exists.
        return true;
    }
}
