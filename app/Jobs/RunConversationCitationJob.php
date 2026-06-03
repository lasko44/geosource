<?php

namespace App\Jobs;

use App\Models\CitationCheck;
use App\Services\Citation\Platforms\ClaudeService;
use App\Services\Citation\Platforms\OpenAIBrowsingService;
use App\Services\Citation\Platforms\PerplexityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a multi-turn conversation on a single platform across an ordered list
 * of CitationChecks. All checks share the same conversation_id and are
 * processed in turn_index order; each platform call sees the running
 * user/assistant message history so later turns inherit context from earlier ones.
 *
 * Built for the v7-conversation study line. The single-turn analog is
 * CheckCitationJob.
 */
class RunConversationCitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  array<int>  $checkIds  Ordered citation_check IDs for the conversation (turn 1..N).
     * @param  string  $platform  One of CitationCheck::PLATFORM_* (claude, openai, perplexity).
     */
    public function __construct(
        public array $checkIds,
        public string $platform,
    ) {}

    public function handle(
        ClaudeService $claudeService,
        OpenAIBrowsingService $openAIService,
        PerplexityService $perplexityService,
    ): void {
        $checks = CitationCheck::whereIn('id', $this->checkIds)
            ->orderBy('turn_index')
            ->get();

        if ($checks->isEmpty()) {
            return;
        }

        $messageHistory = [];

        foreach ($checks as $check) {
            $check->update([
                'status' => CitationCheck::STATUS_PROCESSING,
                'started_at' => now(),
            ]);

            try {
                $query = $check->citationQuery;
                if (! $query) {
                    $this->failCheck($check, 'Citation query not found.');
                    continue;
                }

                $result = match ($this->platform) {
                    'claude' => $claudeService->check($query, $check, $messageHistory),
                    'openai' => $openAIService->check($query, $check, $messageHistory),
                    'perplexity' => $perplexityService->check($query, $check, $messageHistory),
                    default => throw new \RuntimeException("Unsupported platform for conversation: {$this->platform}"),
                };

                $check->update([
                    'is_cited' => $result['is_cited'] ?? false,
                    'ai_response' => $result['ai_response'] ?? null,
                    'citations' => $result['citations'] ?? [],
                    'metadata' => $result['metadata'] ?? null,
                    'status' => CitationCheck::STATUS_COMPLETED,
                    'progress_step' => 'Completed',
                    'progress_percent' => 100,
                    'completed_at' => now(),
                ]);

                // Append this turn to the running history for the next iteration.
                // We use the user-facing query text for the history (the
                // platform-specific prompt buildPrompt() is applied fresh each
                // call inside the service, so we don't want to double-include
                // search-result blocks). The assistant message uses the raw
                // ai_response text the platform returned.
                $messageHistory[] = ['role' => 'user', 'content' => $query->query];
                $messageHistory[] = ['role' => 'assistant', 'content' => Arr::get($result, 'ai_response', '')];

            } catch (Throwable $e) {
                $this->failCheck($check, $e->getMessage());
                // Stop the conversation on first failure — later turns
                // wouldn't make sense without the predecessor's response.
                Log::error('Conversation turn failed', [
                    'check_id' => $check->id,
                    'platform' => $this->platform,
                    'turn_index' => $check->turn_index,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        }
    }

    private function failCheck(CitationCheck $check, string $message): void
    {
        $check->update([
            'status' => CitationCheck::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}
