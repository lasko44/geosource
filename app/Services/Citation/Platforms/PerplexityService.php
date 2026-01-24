<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerplexityService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Perform a citation check using Perplexity API.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.perplexity.api_key');
        $model = config('citations.perplexity.model');
        $baseUrl = config('citations.perplexity.base_url');
        $timeout = config('citations.perplexity.timeout', 60);

        if (! $apiKey) {
            throw new \RuntimeException('Perplexity API key is not configured');
        }

        // Build the prompt
        $prompt = $this->buildPrompt($query);

        // Make the API request
        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful research assistant. Provide comprehensive, accurate information with sources when available. Always cite your sources.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'top_p' => 0.9,
                'return_citations' => true,
                'search_domain_filter' => [],
                'search_recency_filter' => 'month',
            ]);

        if (! $response->successful()) {
            $errorData = $response->json();
            $sanitizedError = $errorData['error']['message'] ?? $errorData['detail'] ?? 'Unknown error (status: ' . $response->status() . ')';
            Log::error('Perplexity API error', [
                'status' => $response->status(),
                'error' => $sanitizedError,
                'query_id' => $query->id,
            ]);

            throw new \RuntimeException('Perplexity API request failed: ' . $sanitizedError);
        }

        $data = $response->json();

        // Extract the response content
        $content = $data['choices'][0]['message']['content'] ?? '';
        $citations = $data['citations'] ?? [];

        // Analyze the response for domain/brand mentions
        $analysis = $this->analyzer->analyze($content, $citations, $query->domain, $query->brand);

        return [
            'ai_response' => $content,
            'citations' => $analysis['citations'],
            'is_cited' => $analysis['is_cited'],
            'metadata' => [
                'model' => $model,
                'tokens' => $data['usage'] ?? null,
                'raw_citations' => $citations,
                'confidence' => $analysis['confidence'],
            ],
        ];
    }

    /**
     * Build the search prompt for the query.
     */
    protected function buildPrompt(CitationQuery $query): string
    {
        $searchQuery = $query->query;

        return sprintf(
            config('citations.check.prompt_template', "Please search for information about: %s\n\nProvide a comprehensive answer with relevant sources and citations."),
            $searchQuery
        );
    }
}
