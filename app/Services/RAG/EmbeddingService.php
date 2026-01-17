<?php

namespace App\Services\RAG;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    public function __construct()
    {
        $this->provider = config('rag.embedding.provider', 'openai');
        $this->model = config('rag.embedding.model', 'text-embedding-3-small');
        $this->dimensions = config('rag.embedding.dimensions', 1536);
    }

    /**
     * Generate embedding for a single text.
     *
     * @return array<int, float>
     */
    public function embed(string $text, bool $cache = true): array
    {
        $text = $this->prepareText($text);

        if ($cache) {
            $cacheKey = 'embedding:'.md5($text.$this->model);

            return Cache::remember($cacheKey, now()->addDays(7), fn () => $this->generateEmbedding($text));
        }

        return $this->generateEmbedding($text);
    }

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param  array<string>  $texts
     * @return array<array<int, float>>
     */
    public function embedBatch(array $texts, bool $cache = true): array
    {
        $texts = array_map(fn ($text) => $this->prepareText($text), $texts);

        if ($cache) {
            $results = [];
            $uncached = [];
            $uncachedIndices = [];

            foreach ($texts as $index => $text) {
                $cacheKey = 'embedding:'.md5($text.$this->model);
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    $results[$index] = $cached;
                } else {
                    $uncached[] = $text;
                    $uncachedIndices[] = $index;
                }
            }

            if (! empty($uncached)) {
                $newEmbeddings = $this->generateBatchEmbeddings($uncached);

                foreach ($newEmbeddings as $i => $embedding) {
                    $originalIndex = $uncachedIndices[$i];
                    $results[$originalIndex] = $embedding;

                    $cacheKey = 'embedding:'.md5($texts[$originalIndex].$this->model);
                    Cache::put($cacheKey, $embedding, now()->addDays(7));
                }
            }

            ksort($results);

            return array_values($results);
        }

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
            return 0;
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

    private function generateEmbedding(string $text): array
    {
        return match ($this->provider) {
            'openai' => $this->openAIEmbed($text),
            'voyage' => $this->voyageEmbed($text),
            default => throw new \RuntimeException("Unknown embedding provider: {$this->provider}"),
        };
    }

    private function generateBatchEmbeddings(array $texts): array
    {
        return match ($this->provider) {
            'openai' => $this->openAIEmbedBatch($texts),
            'voyage' => $this->voyageEmbedBatch($texts),
            default => throw new \RuntimeException("Unknown embedding provider: {$this->provider}"),
        };
    }

    private function openAIEmbed(string $text): array
    {
        $response = Http::withToken(config('rag.openai.api_key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
                'dimensions' => $this->dimensions,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI embedding failed: '.$response->body());
        }

        return $response->json('data.0.embedding');
    }

    private function openAIEmbedBatch(array $texts): array
    {
        // OpenAI supports up to 2048 inputs per request
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
                throw new \RuntimeException('OpenAI batch embedding failed: '.$response->body());
            }

            $data = $response->json('data');

            // Sort by index to maintain order
            usort($data, fn ($a, $b) => $a['index'] <=> $b['index']);

            foreach ($data as $item) {
                $allEmbeddings[] = $item['embedding'];
            }
        }

        return $allEmbeddings;
    }

    private function voyageEmbed(string $text): array
    {
        $response = Http::withToken(config('rag.voyage.api_key'))
            ->timeout(30)
            ->post('https://api.voyageai.com/v1/embeddings', [
                'model' => config('rag.voyage.model', 'voyage-2'),
                'input' => $text,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Voyage AI embedding failed: '.$response->body());
        }

        return $response->json('data.0.embedding');
    }

    private function voyageEmbedBatch(array $texts): array
    {
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
                throw new \RuntimeException('Voyage AI batch embedding failed: '.$response->body());
            }

            $data = $response->json('data');

            foreach ($data as $item) {
                $allEmbeddings[] = $item['embedding'];
            }
        }

        return $allEmbeddings;
    }

    private function prepareText(string $text): string
    {
        // Clean and normalize text
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate to max tokens (roughly 8000 tokens = 32000 chars for safety)
        if (strlen($text) > 32000) {
            $text = substr($text, 0, 32000);
        }

        return $text;
    }
}
