<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSearchService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Check if a domain appears in Google Search results.
     * Uses SerpAPI for search results.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.serpapi.api_key');

        if (! $apiKey) {
            throw new \RuntimeException('SerpAPI key is not configured. Add SERPAPI_API_KEY to your .env file.');
        }

        try {
            $response = Http::timeout(60)
                ->get('https://serpapi.com/search.json', [
                    'api_key' => $apiKey,
                    'q' => $query->query,
                    'engine' => 'google',
                    'num' => 20, // Get top 20 results
                    'gl' => 'us', // Country
                    'hl' => 'en', // Language
                ]);

            if (! $response->successful()) {
                Log::error('SerpAPI Google Search error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query_id' => $query->id,
                ]);

                throw new \RuntimeException('SerpAPI request failed: ' . $response->body());
            }

            $data = $response->json();

            // Extract organic results
            $organicResults = $data['organic_results'] ?? [];
            $aiOverview = $data['ai_overview'] ?? null;
            $knowledgeGraph = $data['knowledge_graph'] ?? null;
            $answerBox = $data['answer_box'] ?? null;

            // Build response and citations
            $citations = [];
            $isCited = false;
            $position = null;

            // Check organic results
            foreach ($organicResults as $index => $result) {
                $link = $result['link'] ?? '';
                $domain = parse_url($link, PHP_URL_HOST);

                if ($this->domainMatches($domain, $query->domain)) {
                    $isCited = true;
                    $position = $index + 1;
                    $citations[] = [
                        'url' => $link,
                        'title' => $result['title'] ?? '',
                        'snippet' => $result['snippet'] ?? '',
                        'position' => $position,
                        'type' => 'organic',
                    ];
                }
            }

            // Check AI Overview if present
            $aiOverviewMention = false;
            if ($aiOverview) {
                $aiOverviewText = $this->extractAiOverviewText($aiOverview);
                $aiOverviewUrls = $this->extractAiOverviewUrls($aiOverview);

                foreach ($aiOverviewUrls as $url) {
                    $domain = parse_url($url, PHP_URL_HOST);
                    if ($this->domainMatches($domain, $query->domain)) {
                        $aiOverviewMention = true;
                        $isCited = true;
                        $citations[] = [
                            'url' => $url,
                            'title' => 'AI Overview Citation',
                            'snippet' => substr($aiOverviewText, 0, 200),
                            'position' => 0,
                            'type' => 'ai_overview',
                        ];
                    }
                }
            }

            // Check Knowledge Graph
            if ($knowledgeGraph) {
                $kgUrl = $knowledgeGraph['website'] ?? $knowledgeGraph['source']['link'] ?? null;
                if ($kgUrl && $this->domainMatches(parse_url($kgUrl, PHP_URL_HOST), $query->domain)) {
                    $isCited = true;
                    $citations[] = [
                        'url' => $kgUrl,
                        'title' => $knowledgeGraph['title'] ?? 'Knowledge Graph',
                        'snippet' => $knowledgeGraph['description'] ?? '',
                        'position' => 0,
                        'type' => 'knowledge_graph',
                    ];
                }
            }

            // Build summary response
            $aiResponse = $this->buildResponseSummary($organicResults, $aiOverview, $position, $query);

            return [
                'is_cited' => $isCited,
                'ai_response' => $aiResponse,
                'citations' => $citations,
                'metadata' => [
                    'total_results' => $data['search_information']['total_results'] ?? null,
                    'search_time' => $data['search_information']['time_taken_displayed'] ?? null,
                    'position' => $position,
                    'in_ai_overview' => $aiOverviewMention,
                    'has_knowledge_graph' => ! empty($knowledgeGraph),
                    'organic_results_checked' => count($organicResults),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Google Search citation check failed', [
                'query_id' => $query->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a domain matches the target domain.
     */
    protected function domainMatches(?string $resultDomain, string $targetDomain): bool
    {
        if (! $resultDomain) {
            return false;
        }

        $resultDomain = strtolower(preg_replace('/^www\./', '', $resultDomain));
        $targetDomain = strtolower(preg_replace('/^www\./', '', $targetDomain));

        return $resultDomain === $targetDomain || str_ends_with($resultDomain, '.' . $targetDomain);
    }

    /**
     * Extract text from AI Overview.
     */
    protected function extractAiOverviewText(array $aiOverview): string
    {
        $text = '';

        if (isset($aiOverview['text'])) {
            $text = $aiOverview['text'];
        } elseif (isset($aiOverview['text_blocks'])) {
            foreach ($aiOverview['text_blocks'] as $block) {
                if (isset($block['snippet'])) {
                    $text .= $block['snippet'] . ' ';
                }
            }
        }

        return trim($text);
    }

    /**
     * Extract URLs from AI Overview.
     */
    protected function extractAiOverviewUrls(array $aiOverview): array
    {
        $urls = [];

        if (isset($aiOverview['sources'])) {
            foreach ($aiOverview['sources'] as $source) {
                if (isset($source['link'])) {
                    $urls[] = $source['link'];
                }
            }
        }

        if (isset($aiOverview['text_blocks'])) {
            foreach ($aiOverview['text_blocks'] as $block) {
                if (isset($block['link'])) {
                    $urls[] = $block['link'];
                }
            }
        }

        return array_unique($urls);
    }

    /**
     * Build a summary response.
     */
    protected function buildResponseSummary(array $organicResults, ?array $aiOverview, ?int $position, CitationQuery $query): string
    {
        $summary = "Google Search Results for: \"{$query->query}\"\n\n";

        if ($position) {
            $summary .= "✓ {$query->domain} found at position #{$position} in organic results.\n\n";
        } else {
            $summary .= "✗ {$query->domain} not found in top " . count($organicResults) . " organic results.\n\n";
        }

        if ($aiOverview) {
            $summary .= "AI Overview present: " . ($this->extractAiOverviewText($aiOverview) ? 'Yes' : 'No') . "\n\n";
        }

        $summary .= "Top 5 Results:\n";
        foreach (array_slice($organicResults, 0, 5) as $index => $result) {
            $pos = $index + 1;
            $title = $result['title'] ?? 'No title';
            $link = $result['link'] ?? '';
            $summary .= "{$pos}. {$title}\n   {$link}\n";
        }

        return $summary;
    }
}
