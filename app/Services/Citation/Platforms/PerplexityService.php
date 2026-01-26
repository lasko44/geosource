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
            ->withoutRedirecting()
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful research assistant. Provide comprehensive, accurate information with sources when available. Always cite your sources with URLs.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'top_p' => 0.9,
                'web_search_options' => [
                    'search_context_size' => 'high',
                ],
            ]);

        if (! $response->successful()) {
            $status = $response->status();
            $errorData = $response->json();

            // Handle redirects specifically
            if ($status === 302 || $status === 301) {
                $location = $response->header('Location');
                Log::error('Perplexity API redirect', [
                    'status' => $status,
                    'location' => $location,
                    'query_id' => $query->id,
                    'base_url' => $baseUrl,
                ]);
                throw new \RuntimeException('Perplexity API redirected (status: ' . $status . '). Check API endpoint configuration. Location: ' . ($location ?? 'unknown'));
            }

            $sanitizedError = $errorData['error']['message'] ?? $errorData['detail'] ?? 'Unknown error (status: ' . $status . ')';
            Log::error('Perplexity API error', [
                'status' => $status,
                'error' => $sanitizedError,
                'query_id' => $query->id,
                'response_body' => $response->body(),
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
