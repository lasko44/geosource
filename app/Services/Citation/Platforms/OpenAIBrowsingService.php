<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIBrowsingService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Perform a citation check using OpenAI with Tavily web search.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $openaiApiKey = config('citations.openai.api_key');
        $tavilyApiKey = config('citations.tavily.api_key');
        $model = config('citations.openai.model', 'gpt-4o');
        $timeout = config('citations.openai.timeout', 90);

        if (empty($openaiApiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured. Add OPENAI_API_KEY to your .env file.');
        }

        if (empty($tavilyApiKey)) {
            throw new \RuntimeException('Tavily API key is not configured. Add TAVILY_API_KEY to your .env file.');
        }

        try {
            // Step 1: Search the web using Tavily
            $searchResults = $this->searchTavily($query->query, $tavilyApiKey);

            if (empty($searchResults)) {
                throw new \RuntimeException('No search results found.');
            }

            // Step 2: Build prompt with search results as context
            $prompt = $this->buildPrompt($query, $searchResults);

            // Step 3: Ask GPT to answer using the search results
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiApiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 2048,
                ]);

            if (! $response->successful()) {
                $errorData = $response->json();
                $sanitizedError = $errorData['error']['message'] ?? 'Unknown error (status: ' . $response->status() . ')';
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'error' => $sanitizedError,
                    'query_id' => $query->id,
                ]);
                throw new \RuntimeException('OpenAI API error: ' . $sanitizedError);
            }

            $data = $response->json();
            $aiResponse = $data['choices'][0]['message']['content'] ?? '';

            // Extract URLs from search results for citation analysis
            $sourceUrls = array_map(fn($r) => $r['url'], $searchResults);

            // Analyze the response for citations
            $analysis = $this->analyzer->analyze(
                $aiResponse,
                $sourceUrls,
                $query->domain,
                $query->brand
            );

            return [
                'is_cited' => $analysis['is_cited'],
                'ai_response' => $aiResponse,
                'citations' => $analysis['citations'],
                'metadata' => [
                    'model' => $data['model'] ?? $model,
                    'usage' => $data['usage'] ?? null,
                    'raw_citations' => $sourceUrls,
                    'search_results_count' => count($searchResults),
                    'confidence' => $analysis['confidence'],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI citation check failed', [
                'query_id' => $query->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Search the web using Tavily Search API.
     */
    private function searchTavily(string $query, string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post('https://api.tavily.com/search', [
                'query' => $query,
                'search_depth' => 'basic',
                'include_answer' => false,
                'include_raw_content' => false,
                'max_results' => 10,
            ]);

        if (! $response->successful()) {
            $errorData = $response->json();
            $sanitizedError = $errorData['detail']['error'] ?? $errorData['detail'] ?? 'Unknown error (status: ' . $response->status() . ')';
            Log::error('Tavily Search API error', [
                'status' => $response->status(),
                'error' => $sanitizedError,
            ]);
            throw new \RuntimeException('Tavily Search API error: ' . $sanitizedError);
        }

        $data = $response->json();
        $results = [];

        foreach ($data['results'] ?? [] as $result) {
            $results[] = [
                'title' => $result['title'] ?? '',
                'url' => $result['url'] ?? '',
                'description' => $result['content'] ?? '',
            ];
        }

        return $results;
    }

    /**
     * Build the prompt with search results as context.
     */
    private function buildPrompt(CitationQuery $query, array $searchResults): string
    {
        $sourcesText = "";
        foreach ($searchResults as $i => $result) {
            $num = $i + 1;
            $sourcesText .= "[{$num}] {$result['title']}\n";
            $sourcesText .= "URL: {$result['url']}\n";
            $sourcesText .= "{$result['description']}\n\n";
        }

        $brandContext = $query->brand
            ? " Pay special attention to mentions of \"{$query->brand}\" or \"{$query->domain}\"."
            : "";

        return <<<PROMPT
You are a helpful AI assistant. Answer the following question using ONLY the search results provided below. You MUST cite your sources using the format [1], [2], etc. and include the full URLs of sources you reference.

## Search Results:
{$sourcesText}

## Question:
{$query->query}

## Instructions:
1. Answer the question thoroughly using the search results above
2. ALWAYS cite sources using [1], [2], etc. format
3. Include the full URL when mentioning a source
4. If a source from "{$query->domain}" is relevant, make sure to cite it{$brandContext}

## Answer:
PROMPT;
    }
}
