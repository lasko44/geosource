<?php

namespace App\Services\GeoStudy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlCollectorService
{
    /**
     * Parse URLs from CSV content.
     * Supports one URL per line or CSV with URL column.
     */
    public function parseCSV(string $content): array
    {
        $urls = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Skip header rows
            if (preg_match('/^(url|link|website|page|href)/i', $line)) {
                continue;
            }

            // If line contains comma, try to extract URL
            if (str_contains($line, ',')) {
                $parts = str_getcsv($line);
                foreach ($parts as $part) {
                    if ($this->isValidUrl(trim($part))) {
                        $urls[] = trim($part);
                        break;
                    }
                }
            } elseif ($this->isValidUrl($line)) {
                $urls[] = $line;
            }
        }

        return array_unique($urls);
    }

    /**
     * Collect URLs from SERP API for given keywords.
     */
    public function collectFromSerp(array $keywords, int $perKeyword = 10): array
    {
        $urls = [];
        $apiKey = config('services.serpapi.key');

        if (empty($apiKey)) {
            Log::warning('SERP API key not configured');
            return [];
        }

        foreach ($keywords as $keyword) {
            try {
                $response = Http::timeout(30)
                    ->get('https://serpapi.com/search', [
                        'api_key' => $apiKey,
                        'engine' => 'google',
                        'q' => $keyword,
                        'num' => $perKeyword,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $organicResults = $data['organic_results'] ?? [];

                    foreach ($organicResults as $result) {
                        if (isset($result['link'])) {
                            $urls[] = [
                                'url' => $result['link'],
                                'metadata' => [
                                    'keyword' => $keyword,
                                    'title' => $result['title'] ?? null,
                                    'position' => $result['position'] ?? null,
                                ],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("SERP API failed for keyword '{$keyword}': {$e->getMessage()}");
            }
        }

        return $urls;
    }

    /**
     * Collect URLs from sitemap(s).
     */
    public function collectFromSitemap(string $sitemapUrl, int $limit = 500): array
    {
        $urls = [];
        $visited = [];

        $this->parseSitemap($sitemapUrl, $urls, $visited, $limit);

        return array_slice($urls, 0, $limit);
    }

    /**
     * Recursively parse sitemap XML.
     */
    private function parseSitemap(string $sitemapUrl, array &$urls, array &$visited, int $limit): void
    {
        // Prevent infinite loops
        if (in_array($sitemapUrl, $visited) || count($urls) >= $limit) {
            return;
        }

        $visited[] = $sitemapUrl;

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; GeoSourceBot/1.0)',
                    'Accept' => 'application/xml, text/xml, */*',
                ])
                ->get($sitemapUrl);

            if (! $response->successful()) {
                Log::warning("Failed to fetch sitemap: {$sitemapUrl} (status: {$response->status()})");
                return;
            }

            $content = $response->body();

            // Check if it's gzipped
            if (str_ends_with(strtolower($sitemapUrl), '.gz')) {
                $content = gzdecode($content);
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if ($xml === false) {
                Log::warning("Failed to parse sitemap XML: {$sitemapUrl}");
                return;
            }

            // Handle sitemap index
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    if (count($urls) >= $limit) {
                        break;
                    }

                    $childUrl = (string) $sitemap->loc;
                    $this->parseSitemap($childUrl, $urls, $visited, $limit);
                }
            }

            // Handle URL set
            if (isset($xml->url)) {
                foreach ($xml->url as $urlEntry) {
                    if (count($urls) >= $limit) {
                        break;
                    }

                    $loc = (string) $urlEntry->loc;
                    if ($this->isValidUrl($loc)) {
                        $urls[] = [
                            'url' => $loc,
                            'metadata' => [
                                'lastmod' => isset($urlEntry->lastmod) ? (string) $urlEntry->lastmod : null,
                                'changefreq' => isset($urlEntry->changefreq) ? (string) $urlEntry->changefreq : null,
                                'priority' => isset($urlEntry->priority) ? (float) $urlEntry->priority : null,
                            ],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error parsing sitemap {$sitemapUrl}: {$e->getMessage()}");
        }
    }

    /**
     * Validate URL format.
     */
    private function isValidUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Must start with http:// or https://
        if (! preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        // Basic URL validation
        $parsed = parse_url($url);
        if (! isset($parsed['host'])) {
            return false;
        }

        // Filter out common non-content URLs
        $skipPatterns = [
            '/\.(jpg|jpeg|png|gif|webp|svg|ico|pdf|zip|gz|tar|rar)$/i',
            '/\/(wp-admin|wp-includes|wp-content\/plugins)\//i',
            '/\/(cart|checkout|account|login|logout|register)\/?$/i',
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return false;
            }
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Extract domain from URL.
     */
    public function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }
}
