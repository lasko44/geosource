<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Perform a citation check using DeepSeek API.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.deepseek.api_key');
        $model = config('citations.deepseek.model', 'deepseek-chat');
        $baseUrl = config('citations.deepseek.base_url', 'https://api.deepseek.com');
        $timeout = config('citations.deepseek.timeout', 90);

        if (! $apiKey) {
            throw new \RuntimeException('DeepSeek API key is not configured. Add DEEPSEEK_API_KEY to your .env file.');
        }

        $prompt = $this->buildPrompt($query);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful research assistant with access to current information. Provide comprehensive, accurate information with sources when available. Always cite your sources with full URLs when possible.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2048,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $errorData['detail'] ?? 'Unknown error (status: ' . $response->status() . ')';

                Log::error('DeepSeek API error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'query_id' => $query->id,
                ]);

                throw new \RuntimeException('DeepSeek API request failed: ' . $errorMessage);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // DeepSeek doesn't provide structured citations, so we extract from the response
            $extractedUrls = $this->extractUrlsFromResponse($content);

            // Analyze the response for domain/brand mentions
            $analysis = $this->analyzer->analyze($content, $extractedUrls, $query->domain, $query->brand);

            return [
                'ai_response' => $content,
                'citations' => $analysis['citations'],
                'is_cited' => $analysis['is_cited'],
                'metadata' => [
                    'model' => $model,
                    'tokens' => $data['usage'] ?? null,
                    'raw_citations' => $extractedUrls,
                    'confidence' => $analysis['confidence'],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek citation check failed', [
                'query_id' => $query->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract URLs from the response text.
     */
    protected function extractUrlsFromResponse(string $content): array
    {
        $urls = [];

        // Match URLs in the response
        preg_match_all(
            '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/',
            $content,
            $matches
        );

        if (! empty($matches[0])) {
            $urls = array_unique($matches[0]);
        }

        return array_values($urls);
    }

    /**
     * Build the search prompt for the query.
     */
    protected function buildPrompt(CitationQuery $query): string
    {
        $brandContext = $query->brand
            ? " The brand associated with this domain is \"{$query->brand}\"."
            : '';

        return sprintf(
            "Please search for and provide comprehensive information about: %s\n\n" .
            "Important: If you mention or reference the website \"%s\" or any pages from this domain, please include the full URL.%s\n\n" .
            "Provide a detailed, informative response with sources where applicable.",
            $query->query,
            $query->domain,
            $brandContext
        );
    }
}
