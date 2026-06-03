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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

            // Sanitize UTF-8 in results to prevent JSON encoding failures
            $aiResponse = mb_convert_encoding($result['ai_response'] ?? '', 'UTF-8', 'UTF-8');
            $citations = json_decode(json_encode($result['citations'] ?? [], JSON_INVALID_UTF8_SUBSTITUTE), true);
            $metadata = json_decode(json_encode($result['metadata'] ?? [], JSON_INVALID_UTF8_SUBSTITUTE), true);

            // Update check with results
            $this->check->update([
                'status' => CitationCheck::STATUS_COMPLETED,
                'is_cited' => $result['is_cited'],
                'ai_response' => $aiResponse,
                'citations' => $citations,
                'metadata' => $metadata,
                'progress_step' => 'Completed',
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);

            // Process completion and create alerts if needed
            $citationService->processCheckCompletion($this->check);

            // Dispatch the recommendation-strength follow-up classifier so the
            // UI can show users not just "you were cited" but "you were cited
            // at position #3 in a top-3 list with a buy link." Async via queue
            // so the main citation check returns immediately.
            if (! empty($aiResponse) && $result['is_cited'] ?? false) {
                \App\Jobs\AnalyzeRecommendationStrengthJob::dispatch(
                    $this->check->id,
                    $query->getBrandIdentifiers(),
                );
            }

            // Record correlation data for continuous algorithm improvement
            try {
                app(\App\Services\Analytics\CorrelationService::class)->recordFromCitation($query);
            } catch (\Exception $e) {
                Log::warning('Failed to record citation correlation', ['query_id' => $query->id, 'error' => $e->getMessage()]);
            }

            // Update the query's last checked timestamp
            $query->update(['last_checked_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Citation check failed', [
                'check_id' => $this->check->id,
                'platform' => $this->check->platform,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed($this->sanitizeErrorMessage($e), $tokenService);
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
     * Sanitize exception messages to never expose internal details to users.
     */
    private function sanitizeErrorMessage(\Exception $e): string
    {
        if ($e instanceof QueryException) {
            return 'An internal error occurred. Please try again later.';
        }

        if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'API request failed')) {
            return 'The AI platform is temporarily unavailable. Please try again in a few minutes.';
        }

        if (str_contains($e->getMessage(), 'SQLSTATE') || str_contains($e->getMessage(), 'Connection')) {
            return 'An internal error occurred. Please try again later.';
        }

        if (str_contains($e->getMessage(), 'UTF-8') || str_contains($e->getMessage(), 'JSON') || str_contains($e->getMessage(), 'encode')) {
            return 'The AI response contained unexpected characters. Please try again.';
        }

        if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
            return 'The request timed out. Please try again.';
        }

        if (str_contains($e->getMessage(), 'rate limit') || str_contains($e->getMessage(), '429')) {
            return 'Rate limited by AI platform. Please try again in a few minutes.';
        }

        return 'Check failed. Please try again later.';
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
            // Guest citation checks (visitor_id set, no user) are always valid
            return $this->check->visitor_id !== null;
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
