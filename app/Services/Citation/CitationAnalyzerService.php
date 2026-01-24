<?php

namespace App\Services\Citation;

class CitationAnalyzerService
{
    /**
     * Analyze AI response for domain/brand citations.
     */
    public function analyze(string $content, array $citations, string $domain, ?string $brand): array
    {
        $results = [
            'is_cited' => false,
            'citations' => [],
            'confidence' => 0,
            'domain_mentioned' => false,
            'brand_mentioned' => false,
        ];

        // Normalize the domain for matching
        $normalizedDomain = $this->normalizeDomain($domain);
        $domainVariants = $this->getDomainVariants($normalizedDomain);

        // Check content for domain mentions
        $domainMentions = $this->findDomainMentions($content, $domainVariants);
        $results['domain_mentioned'] = count($domainMentions) > 0;

        // Check content for brand mentions
        $brandMentions = [];
        if ($brand) {
            $brandMentions = $this->findBrandMentions($content, $brand);
            $results['brand_mentioned'] = count($brandMentions) > 0;
        }

        // Check citations/URLs for domain
        $citationMatches = $this->checkCitationsForDomain($citations, $domainVariants);

        // Build citation details
        if ($results['domain_mentioned'] || count($citationMatches) > 0) {
            $results['is_cited'] = true;

            // Add domain citation details
            foreach ($domainMentions as $mention) {
                $results['citations'][] = [
                    'type' => 'domain_mention',
                    'match' => $mention['match'],
                    'context' => $mention['context'],
                    'position' => $mention['position'],
                ];
            }

            // Add URL citation details
            foreach ($citationMatches as $match) {
                $results['citations'][] = [
                    'type' => 'url_citation',
                    'url' => $match['url'],
                    'domain' => $match['domain'],
                ];
            }
        }

        // Add brand mentions if found
        if ($results['brand_mentioned']) {
            foreach ($brandMentions as $mention) {
                $results['citations'][] = [
                    'type' => 'brand_mention',
                    'match' => $mention['match'],
                    'context' => $mention['context'],
                    'position' => $mention['position'],
                ];
            }
        }

        // Calculate confidence score
        $results['confidence'] = $this->calculateConfidence($results);

        return $results;
    }

    /**
     * Normalize a domain for matching.
     */
    protected function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('/^https?:\/\//', '', $domain);

        // Remove www.
        $domain = preg_replace('/^www\./', '', $domain);

        // Remove trailing slash
        $domain = rtrim($domain, '/');

        // Lowercase
        return strtolower($domain);
    }

    /**
     * Get domain variants for matching.
     */
    protected function getDomainVariants(string $domain): array
    {
        return [
            $domain,
            'www.'.$domain,
            'https://'.$domain,
            'https://www.'.$domain,
            'http://'.$domain,
            'http://www.'.$domain,
        ];
    }

    /**
     * Find domain mentions in content.
     */
    protected function findDomainMentions(string $content, array $domainVariants): array
    {
        $mentions = [];

        foreach ($domainVariants as $variant) {
            $pattern = '/'.preg_quote($variant, '/').'/i';

            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $position = $match[1];
                    $context = $this->extractContext($content, $position, strlen($match[0]));

                    $mentions[] = [
                        'match' => $match[0],
                        'position' => $position,
                        'context' => $context,
                    ];
                }
            }
        }

        return $mentions;
    }

    /**
     * Find brand mentions in content.
     */
    protected function findBrandMentions(string $content, string $brand): array
    {
        $mentions = [];

        // Create pattern with word boundaries
        $pattern = '/\b'.preg_quote($brand, '/').'\b/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $position = $match[1];
                $context = $this->extractContext($content, $position, strlen($match[0]));

                $mentions[] = [
                    'match' => $match[0],
                    'position' => $position,
                    'context' => $context,
                ];
            }
        }

        return $mentions;
    }

    /**
     * Check citations/URLs for domain matches.
     */
    protected function checkCitationsForDomain(array $citations, array $domainVariants): array
    {
        $matches = [];

        foreach ($citations as $citation) {
            // Handle both string URLs and structured citations
            $url = is_string($citation) ? $citation : ($citation['url'] ?? $citation['link'] ?? '');

            if (! $url) {
                continue;
            }

            $citationDomain = $this->normalizeDomain($url);

            foreach ($domainVariants as $variant) {
                $normalizedVariant = $this->normalizeDomain($variant);

                // Check if citation domain contains our domain
                if (str_contains($citationDomain, $normalizedVariant)) {
                    $matches[] = [
                        'url' => $url,
                        'domain' => $citationDomain,
                    ];
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Extract context around a match position.
     */
    protected function extractContext(string $content, int $position, int $matchLength, int $contextLength = 100): string
    {
        $start = max(0, $position - $contextLength);
        $end = min(strlen($content), $position + $matchLength + $contextLength);

        $context = substr($content, $start, $end - $start);

        // Add ellipsis if truncated
        if ($start > 0) {
            $context = '...'.$context;
        }
        if ($end < strlen($content)) {
            $context .= '...';
        }

        return $context;
    }

    /**
     * Calculate confidence score based on findings.
     */
    protected function calculateConfidence(array $results): float
    {
        $score = 0;

        // URL citations are highest confidence
        $urlCitations = array_filter($results['citations'], fn ($c) => $c['type'] === 'url_citation');
        $score += count($urlCitations) * 40;

        // Domain mentions are medium confidence
        $domainMentions = array_filter($results['citations'], fn ($c) => $c['type'] === 'domain_mention');
        $score += count($domainMentions) * 25;

        // Brand mentions add to confidence
        $brandMentions = array_filter($results['citations'], fn ($c) => $c['type'] === 'brand_mention');
        $score += count($brandMentions) * 15;

        // Cap at 100
        return min(100, $score);
    }

    /**
     * Extract all URLs from content.
     */
    public function extractUrls(string $content): array
    {
        $pattern = '/https?:\/\/[^\s\)\]\>\"\']+/i';
        preg_match_all($pattern, $content, $matches);

        return array_unique($matches[0] ?? []);
    }

    /**
     * Get a summary of the citation analysis.
     */
    public function getSummary(array $analysisResult): string
    {
        if (! $analysisResult['is_cited']) {
            return 'Your domain was not cited in this response.';
        }

        $parts = [];

        $urlCount = count(array_filter($analysisResult['citations'], fn ($c) => $c['type'] === 'url_citation'));
        $domainCount = count(array_filter($analysisResult['citations'], fn ($c) => $c['type'] === 'domain_mention'));
        $brandCount = count(array_filter($analysisResult['citations'], fn ($c) => $c['type'] === 'brand_mention'));

        if ($urlCount > 0) {
            $parts[] = $urlCount.' direct URL '.($urlCount === 1 ? 'citation' : 'citations');
        }

        if ($domainCount > 0) {
            $parts[] = $domainCount.' domain '.($domainCount === 1 ? 'mention' : 'mentions');
        }

        if ($brandCount > 0) {
            $parts[] = $brandCount.' brand '.($brandCount === 1 ? 'mention' : 'mentions');
        }

        return 'Your domain was cited with: '.implode(', ', $parts).'.';
    }
}
