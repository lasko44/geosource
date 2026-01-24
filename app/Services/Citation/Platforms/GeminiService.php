<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function __construct(
        private CitationAnalyzerService $analyzerService
    ) {}

    /**
     * Check if a domain is cited by Gemini for a given query.
     * Uses Google Search Grounding for real-time web search.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.gemini.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured. Add GOOGLE_AI_API_KEY to your .env file.');
        }

        $prompt = $this->buildPrompt($query);

        try {
            // Use gemini-2.0-flash for grounding support
            $model = config('citations.gemini.model', 'gemini-2.0-flash');

            $response = Http::timeout(90)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'tools' => [
                        ['google_search' => new \stdClass()],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API error response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Gemini API error: ' . $response->body());
            }

            $data = $response->json();
            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Extract grounding sources from the response
            $groundingSources = $this->extractGroundingSources($data);

            // Analyze the response for citations using both text and grounding sources
            $analysis = $this->analyzerService->analyze(
                $aiResponse,
                $groundingSources,
                $query->domain,
                $query->brand
            );

            return [
                'is_cited' => $analysis['is_cited'],
                'ai_response' => $aiResponse,
                'citations' => $analysis['citations'],
                'metadata' => [
                    'model' => $model,
                    'usage' => $data['usageMetadata'] ?? null,
                    'raw_citations' => $groundingSources,
                    'grounding_metadata' => $data['candidates'][0]['groundingMetadata'] ?? null,
                    'confidence' => $analysis['confidence'],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Gemini citation check failed', [
                'query_id' => $query->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract grounding sources (URLs) from Gemini response.
     */
    private function extractGroundingSources(array $data): array
    {
        $sources = [];

        // Extract from groundingMetadata
        $groundingMetadata = $data['candidates'][0]['groundingMetadata'] ?? null;

        if ($groundingMetadata) {
            Log::debug('Gemini grounding metadata', ['metadata' => $groundingMetadata]);

            // Extract from groundingChunks (primary source of URLs)
            $chunks = $groundingMetadata['groundingChunks'] ?? [];
            foreach ($chunks as $chunk) {
                if (isset($chunk['web']['uri'])) {
                    $sources[] = $chunk['web']['uri'];
                }
            }

            // Extract from searchEntryPoint if available
            if (isset($groundingMetadata['searchEntryPoint']['renderedContent'])) {
                // Parse URLs from rendered content if needed
                preg_match_all('/href=["\']([^"\']+)["\']/', $groundingMetadata['searchEntryPoint']['renderedContent'], $matches);
                if (!empty($matches[1])) {
                    $sources = array_merge($sources, $matches[1]);
                }
            }

            // Extract from webSearchQueries - these show what was searched
            $searchQueries = $groundingMetadata['webSearchQueries'] ?? [];

            // Extract from retrievalMetadata if available
            if (isset($groundingMetadata['retrievalMetadata']['webDynamicRetrievalScore'])) {
                Log::debug('Gemini retrieval score', ['score' => $groundingMetadata['retrievalMetadata']['webDynamicRetrievalScore']]);
            }
        }

        return array_unique(array_filter($sources));
    }

    /**
     * Build the prompt for the citation check.
     */
    private function buildPrompt(CitationQuery $query): string
    {
        $brandContext = $query->brand
            ? " The brand associated with this domain is \"{$query->brand}\"."
            : '';

        return <<<PROMPT
You are a helpful AI assistant with access to current information. Answer the following question thoroughly and cite your sources when possible.

Question: {$query->query}

Important: If you mention or reference the website "{$query->domain}" or any pages from this domain in your response, please include the full URL.{$brandContext}

Provide a detailed, informative response with sources where applicable.
PROMPT;
    }
}
