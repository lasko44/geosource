<?php

namespace App\Services\Citation\Platforms;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    public function __construct(
        protected CitationAnalyzerService $analyzer
    ) {}

    /**
     * Check if a domain/brand is mentioned in YouTube videos.
     * Uses SerpAPI for YouTube search results.
     */
    public function check(CitationQuery $query, CitationCheck $check): array
    {
        $apiKey = config('citations.serpapi.api_key');

        if (! $apiKey) {
            throw new \RuntimeException('SerpAPI key is not configured. Add SERPAPI_API_KEY to your .env file.');
        }

        try {
            // Search YouTube for the query + domain/brand
            $searchQuery = $query->query;
            if ($query->brand) {
                $searchQuery .= ' ' . $query->brand;
            }

            $response = Http::timeout(60)
                ->get('https://serpapi.com/search.json', [
                    'api_key' => $apiKey,
                    'search_query' => $searchQuery,
                    'engine' => 'youtube',
                ]);

            if (! $response->successful()) {
                Log::error('SerpAPI YouTube Search error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query_id' => $query->id,
                ]);

                throw new \RuntimeException('SerpAPI YouTube request failed: ' . $response->body());
            }

            $data = $response->json();

            // Extract video results
            $videoResults = $data['video_results'] ?? [];

            // Build response and citations
            $citations = [];
            $isCited = false;
            $mentionCount = 0;

            // Check for domain/brand mentions in video titles, descriptions, and channel names
            foreach ($videoResults as $index => $video) {
                $title = $video['title'] ?? '';
                $description = $video['description'] ?? '';
                $channelName = $video['channel']['name'] ?? '';
                $link = $video['link'] ?? '';

                $hasMention = $this->checkForMention($title, $query->domain, $query->brand)
                    || $this->checkForMention($description, $query->domain, $query->brand)
                    || $this->checkForMention($channelName, $query->domain, $query->brand);

                if ($hasMention) {
                    $isCited = true;
                    $mentionCount++;
                    $citations[] = [
                        'url' => $link,
                        'title' => $title,
                        'snippet' => $description,
                        'position' => $index + 1,
                        'type' => 'youtube_video',
                        'channel' => $channelName,
                        'views' => $video['views'] ?? null,
                        'published_date' => $video['published_date'] ?? null,
                        'thumbnail' => $video['thumbnail']['static'] ?? null,
                    ];
                }
            }

            // Build summary response
            $aiResponse = $this->buildResponseSummary($videoResults, $citations, $query);

            return [
                'is_cited' => $isCited,
                'ai_response' => $aiResponse,
                'citations' => $citations,
                'metadata' => [
                    'total_videos_checked' => count($videoResults),
                    'mention_count' => $mentionCount,
                    'search_query' => $searchQuery,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('YouTube citation check failed', [
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
     * Build a summary response.
     */
    protected function buildResponseSummary(array $videoResults, array $citations, CitationQuery $query): string
    {
        $summary = "YouTube Search Results for: \"{$query->query}\"\n\n";

        if (count($citations) > 0) {
            $summary .= "✓ Found " . count($citations) . " video(s) mentioning {$query->domain}" . ($query->brand ? " or {$query->brand}" : "") . ".\n\n";

            $summary .= "Videos with mentions:\n";
            foreach ($citations as $index => $citation) {
                $pos = $index + 1;
                $summary .= "{$pos}. {$citation['title']}\n";
                $summary .= "   Channel: {$citation['channel']}\n";
                $summary .= "   {$citation['url']}\n\n";
            }
        } else {
            $summary .= "✗ No videos found mentioning {$query->domain}" . ($query->brand ? " or {$query->brand}" : "") . " in top " . count($videoResults) . " results.\n\n";
        }

        $summary .= "Top 5 Video Results:\n";
        foreach (array_slice($videoResults, 0, 5) as $index => $video) {
            $pos = $index + 1;
            $title = $video['title'] ?? 'No title';
            $channel = $video['channel']['name'] ?? 'Unknown';
            $summary .= "{$pos}. {$title} (by {$channel})\n";
        }

        return $summary;
    }
}
