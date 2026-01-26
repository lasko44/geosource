<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Check if a domain/brand is mentioned on Facebook.
     * Uses Google site search via SerpAPI since Facebook doesn't have a public search API.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.serpapi.api_key');

        if (! $apiKey) {
            throw new \RuntimeException('SerpAPI key is not configured. Add SERPAPI_API_KEY to your .env file.');
        }

        try {
            // Search Facebook via Google site search
            $searchTerms = $query->query;
            if ($query->brand) {
                $searchTerms .= ' ' . $query->brand;
            }

            // Add domain to search for mentions
            $searchTerms .= ' ' . $query->domain;

            $response = Http::timeout(60)
                ->get('https://serpapi.com/search.json', [
                    'api_key' => $apiKey,
                    'q' => "site:facebook.com {$searchTerms}",
                    'engine' => 'google',
                    'num' => 20,
                    'gl' => 'us',
                    'hl' => 'en',
                ]);

            if (! $response->successful()) {
                Log::error('SerpAPI Facebook Search error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query_id' => $query->id,
                ]);

                throw new \RuntimeException('SerpAPI Facebook request failed: ' . $response->body());
            }

            $data = $response->json();

            // Extract organic results
            $organicResults = $data['organic_results'] ?? [];

            // Build response and citations
            $citations = [];
            $isCited = false;

            foreach ($organicResults as $index => $result) {
                $title = $result['title'] ?? '';
                $snippet = $result['snippet'] ?? '';
                $link = $result['link'] ?? '';

                // Check if the result mentions the domain or brand
                $hasMention = $this->checkForMention($title, $query->domain, $query->brand)
                    || $this->checkForMention($snippet, $query->domain, $query->brand);

                if ($hasMention) {
                    $isCited = true;
                    $citations[] = [
                        'url' => $link,
                        'title' => $title,
                        'snippet' => $snippet,
                        'position' => $index + 1,
                        'type' => $this->detectFacebookContentType($link),
                    ];
                }
            }

            // Build summary response
            $aiResponse = $this->buildResponseSummary($organicResults, $citations, $query);

            return [
                'is_cited' => $isCited,
                'ai_response' => $aiResponse,
                'citations' => $citations,
                'metadata' => [
                    'total_results' => $data['search_information']['total_results'] ?? null,
                    'results_checked' => count($organicResults),
                    'mention_count' => count($citations),
                    'search_query' => "site:facebook.com {$searchTerms}",
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Facebook citation check failed', [
                'query_id' => $query->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if text contains domain or brand mention.
     */
    protected function checkForMention(string $text, string $domain, ?string $brand): bool
    {
        $text = strtolower($text);
        $domain = strtolower(preg_replace('/^www\./', '', $domain));

        // Check for domain
        if (str_contains($text, $domain)) {
            return true;
        }

        // Check for brand
        if ($brand && str_contains($text, strtolower($brand))) {
            return true;
        }

        return false;
    }

    /**
     * Detect Facebook content type from URL.
     */
    protected function detectFacebookContentType(string $url): string
    {
        if (str_contains($url, '/posts/')) {
            return 'post';
        } elseif (str_contains($url, '/videos/')) {
            return 'video';
        } elseif (str_contains($url, '/groups/')) {
            return 'group';
        } elseif (str_contains($url, '/events/')) {
            return 'event';
        } elseif (str_contains($url, '/pages/')) {
            return 'page';
        } elseif (preg_match('/facebook\.com\/[a-zA-Z0-9.]+\/?$/', $url)) {
            return 'profile_or_page';
        }

        return 'unknown';
    }

    /**
     * Build a summary response.
     */
    protected function buildResponseSummary(array $organicResults, array $citations, CitationQuery $query): string
    {
        $summary = "Facebook Search Results for: \"{$query->query}\"\n";
        $summary .= "Looking for mentions of: {$query->domain}" . ($query->brand ? " / {$query->brand}" : "") . "\n\n";

        if (count($citations) > 0) {
            $summary .= "✓ Found " . count($citations) . " Facebook mention(s).\n\n";

            $summary .= "Mentions found:\n";
            foreach ($citations as $index => $citation) {
                $pos = $index + 1;
                $type = ucfirst($citation['type']);
                $summary .= "{$pos}. [{$type}] {$citation['title']}\n";
                $summary .= "   {$citation['url']}\n";
                if ($citation['snippet']) {
                    $summary .= "   " . substr($citation['snippet'], 0, 100) . "...\n";
                }
                $summary .= "\n";
            }
        } else {
            $summary .= "✗ No Facebook mentions found for {$query->domain}" . ($query->brand ? " or {$query->brand}" : "") . ".\n\n";
        }

        if (count($organicResults) > 0) {
            $summary .= "Top Facebook Results:\n";
            foreach (array_slice($organicResults, 0, 5) as $index => $result) {
                $pos = $index + 1;
                $title = $result['title'] ?? 'No title';
                $summary .= "{$pos}. {$title}\n";
            }
        }

        return $summary;
    }
}
