<?php

namespace App\Services\RAG;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Service for generating vector embeddings from text.
 *
 * Supports multiple providers: OpenAI, Voyage AI, or local models.
 */
class EmbeddingService
{
    private string $provider;

    private string $model;

    private int $dimensions;

    /**
     * Rough token limit and token->char ratio used for safe truncation.
     * Using conservative ratio of ~3 chars/token for safety with varied content.
     */
    private int $maxTokens = 7500; // well below 8192 for safety margin

    private float $charsPerToken = 3.0; // conservative estimate (actual varies 2.5-4)

    /**
     * Rate limiting configuration for embedding API calls.
     * Prevents cost exhaustion attacks on OpenAI API.
     */
    private int $rateLimitPerMinute = 60; // Max embedding requests per minute per team

    private int $rateLimitBatchPerMinute = 10; // Max batch requests per minute per team

    public function __construct()
    {
        $this->provider = config('rag.embedding.provider', 'openai');
        $this->model = config('rag.embedding.model', 'text-embedding-3-small');
        $this->dimensions = config('rag.embedding.dimensions', 1536);
        $this->rateLimitPerMinute = config('rag.embedding.rate_limit_per_minute', 60);
        $this->rateLimitBatchPerMinute = config('rag.embedding.rate_limit_batch_per_minute', 10);
    }

    /**
     * Check rate limit for embedding requests.
     *
     * @throws \App\Exceptions\RateLimitExceededException
     */
    private function checkRateLimit(?int $teamId, bool $isBatch = false): void
    {
        // Use team ID for rate limiting, or fall back to IP-based limiting
        $key = $teamId
            ? "embedding_rate_limit:team:{$teamId}"
            : 'embedding_rate_limit:global:'.request()?->ip();

        $limit = $isBatch ? $this->rateLimitBatchPerMinute : $this->rateLimitPerMinute;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Embedding rate limit exceeded', [
                'team_id' => $teamId,
                'key' => $key,
                'retry_after' => $seconds,
            ]);
            throw new \App\Exceptions\RateLimitExceededException(
                "Embedding rate limit exceeded. Please wait {$seconds} seconds before trying again.",
                $seconds
            );
        }

        RateLimiter::hit($key, 60); // 60 second decay
    }

    /**
     * Generate embedding for a single text.
     *
     * @param  int|null  $teamId  Optional team ID for cache isolation (prevents cross-team data inference)
     * @return array<int, float>
     *
     * @throws \App\Exceptions\RateLimitExceededException
     */
    public function embed(string $text, bool $cache = true, ?int $teamId = null): array
    {
        $text = $this->prepareText($text);

        if ($cache) {
            // Include teamId in cache key to prevent cross-team data inference attacks
            // This ensures teams can't infer what content other teams have analyzed via cache timing
            $cacheKey = 'embedding:'.hash('sha256', $text.$this->model.($teamId ? ":team:{$teamId}" : ''));

            // Check if we have a cached result before applying rate limit
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Only apply rate limit for actual API calls
            $this->checkRateLimit($teamId, false);

            $embedding = $this->generateEmbedding($text);
            Cache::put($cacheKey, $embedding, now()->addDays(7));

            return $embedding;
        }

        // Apply rate limit for non-cached requests
        $this->checkRateLimit($teamId, false);

        return $this->generateEmbedding($text);
    }

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param  array<string>  $texts
     * @param  int|null  $teamId  Optional team ID for cache isolation (prevents cross-team data inference)
     * @return array<array<int, float>>
     *
     * @throws \App\Exceptions\RateLimitExceededException
     */
    public function embedBatch(array $texts, bool $cache = true, ?int $teamId = null): array
    {
        // Enforce maximum batch size to prevent resource exhaustion
        $maxBatchSize = 100;
        if (count($texts) > $maxBatchSize) {
            throw new \InvalidArgumentException("Batch size exceeds maximum of {$maxBatchSize} texts.");
        }

        // Normalize and truncate all texts first
        $texts = array_map(fn ($text) => $this->prepareText($text), $texts);

        // Cache suffix for team isolation
        $teamSuffix = $teamId ? ":team:{$teamId}" : '';

        if ($cache) {
            $results = [];
            $uncached = [];
            $uncachedIndices = [];

            foreach ($texts as $index => $text) {
                // Include teamId in cache key to prevent cross-team data inference
                $cacheKey = 'embedding:'.hash('sha256', $text.$this->model.$teamSuffix);
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    $results[$index] = $cached;
                } else {
                    $uncached[] = $text;
                    $uncachedIndices[] = $index;
                }
            }

            if (! empty($uncached)) {
                // Apply rate limit only when making actual API calls
                $this->checkRateLimit($teamId, true);

                $newEmbeddings = $this->generateBatchEmbeddings($uncached);

                foreach ($newEmbeddings as $i => $embedding) {
                    $originalIndex = $uncachedIndices[$i];
                    $results[$originalIndex] = $embedding;

                    $cacheKey = 'embedding:'.hash('sha256', $texts[$originalIndex].$this->model.$teamSuffix);
                    Cache::put($cacheKey, $embedding, now()->addDays(7));
                }
            }

            ksort($results);

            return array_values($results);
        }

        // Apply rate limit for non-cached batch requests
        $this->checkRateLimit($teamId, true);

        return $this->generateBatchEmbeddings($texts);
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have the same dimensions');
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0, $count = count($a); $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Get the embedding dimensions.
     */
    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * Get current provider.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get current model.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Provider dispatch for single embedding.
     */
    private function generateEmbedding(string $text): array
    {
        return match ($this->provider) {
            'openai' => $this->openAIEmbed($text),
            'voyage' => $this->voyageEmbed($text),
            default => throw new \RuntimeException("Unknown embedding provider: {$this->provider}"),
        };
    }

    /**
     * Provider dispatch for batch embeddings.
     *
     * @param  array<string>  $texts
     * @return array<array<int, float>>
     */
    private function generateBatchEmbeddings(array $texts): array
    {
        return match ($this->provider) {
            'openai' => $this->openAIEmbedBatch($texts),
            'voyage' => $this->voyageEmbedBatch($texts),
            default => throw new \RuntimeException("Unknown embedding provider: {$this->provider}"),
        };
    }

    /**
     * OpenAI single embedding with safe truncation.
     *
     * @return array<int, float>
     */
    private function openAIEmbed(string $text): array
    {
        $text = $this->truncateForModel($text);

        $response = Http::withToken(config('rag.openai.api_key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
                'dimensions' => $this->dimensions,
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Embedding generation failed. Please try again later.');
        }

        return $response->json('data.0.embedding') ?? [];
    }

    /**
     * OpenAI batch embedding with safe truncation.
     *
     * @param  array<string>  $texts
     * @return array<array<int, float>>
     */
    private function openAIEmbedBatch(array $texts): array
    {
        // Pre\-truncate everything for extra safety
        $texts = array_map(fn ($t) => $this->truncateForModel($t), $texts);

        // OpenAI supports many inputs per request; we keep batch smallish and predictable
        $chunks = array_chunk($texts, 100);
        $allEmbeddings = [];

        foreach ($chunks as $chunk) {
            $response = Http::withToken(config('rag.openai.api_key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $this->model,
                    'input' => $chunk,
                    'dimensions' => $this->dimensions,
                ]);

            if (! $response->successful()) {
                Log::error('OpenAI batch embedding failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Batch embedding generation failed. Please try again later.');
            }

            $data = $response->json('data') ?? [];

            // Sort by index to maintain order
            usort($data, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

            foreach ($data as $item) {
                $allEmbeddings[] = $item['embedding'] ?? [];
            }
        }

        return $allEmbeddings;
    }

    /**
     * Voyage single embedding with safe truncation.
     *
     * @return array<int, float>
     */
    private function voyageEmbed(string $text): array
    {
        $text = $this->truncateForModel($text);

        $response = Http::withToken(config('rag.voyage.api_key'))
            ->timeout(30)
            ->post('https://api.voyageai.com/v1/embeddings', [
                'model' => config('rag.voyage.model', 'voyage-2'),
                'input' => $text,
            ]);

        if (! $response->successful()) {
            Log::error('Voyage AI embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Embedding generation failed. Please try again later.');
        }

        return $response->json('data.0.embedding') ?? [];
    }

    /**
     * Voyage batch embedding with safe truncation.
     *
     * @param  array<string>  $texts
     * @return array<array<int, float>>
     */
    private function voyageEmbedBatch(array $texts): array
    {
        $texts = array_map(fn ($t) => $this->truncateForModel($t), $texts);

        $chunks = array_chunk($texts, 128);
        $allEmbeddings = [];

        foreach ($chunks as $chunk) {
            $response = Http::withToken(config('rag.voyage.api_key'))
                ->timeout(60)
                ->post('https://api.voyageai.com/v1/embeddings', [
                    'model' => config('rag.voyage.model', 'voyage-2'),
                    'input' => $chunk,
                ]);

            if (! $response->successful()) {
                Log::error('Voyage AI batch embedding failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Batch embedding generation failed. Please try again later.');
            }

            $data = $response->json('data') ?? [];

            foreach ($data as $item) {
                $allEmbeddings[] = $item['embedding'] ?? [];
            }
        }

        return $allEmbeddings;
    }

    /**
     * Basic cleanup and normalization, then length\-based truncation.
     */
    private function prepareText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Initial coarse truncation by chars
        $maxChars = (int) floor($this->maxTokens * $this->charsPerToken);
        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }

    /**
     * Final safety truncation right before hitting a provider.
     */
    private function truncateForModel(string $text): string
    {
        $maxChars = (int) floor($this->maxTokens * $this->charsPerToken);

        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }
}
