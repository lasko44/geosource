<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on citation and source quality.
 *
 * PRO TIER FEATURE
 *
 * Measures:
 * - External links to authoritative sources
 * - Citation formatting and context
 * - Data/statistics with sources
 * - Reference diversity
 */
class CitationScorer implements ScorerInterface
{
    private const MAX_SCORE = 12;

    private const AUTHORITATIVE_DOMAINS = [
        // Government
        '.gov', '.gov.uk', '.gov.au', '.gc.ca',
        // Education
        '.edu', '.ac.uk', '.edu.au',
        // Major institutions
        'who.int', 'un.org', 'europa.eu',
        // Research/Academic
        'pubmed.ncbi.nlm.nih.gov', 'scholar.google.com', 'arxiv.org',
        'nature.com', 'sciencedirect.com', 'springer.com', 'wiley.com',
        'jstor.org', 'researchgate.net',
        // Trusted news/reference
        'reuters.com', 'apnews.com', 'bbc.com', 'nytimes.com',
        'wikipedia.org', 'britannica.com',
        // Industry standards
        'w3.org', 'ietf.org', 'iso.org',
    ];

    private const REPUTABLE_DOMAINS = [
        // Tech
        'github.com', 'stackoverflow.com', 'mdn.mozilla.org',
        // Business
        'forbes.com', 'bloomberg.com', 'wsj.com', 'economist.com',
        'hbr.org', 'mckinsey.com',
        // Health
        'mayoclinic.org', 'webmd.com', 'healthline.com', 'nih.gov',
        // General reference
        'statista.com', 'pewresearch.org', 'gallup.com',
    ];

    public function score(string $content, array $context = []): array
    {
        $url = $context['url'] ?? null;
        $baseDomain = $this->extractDomain($url);

        $details = [
            'external_links' => $this->analyzeExternalLinks($content, $baseDomain),
            'citations' => $this->analyzeCitations($content),
            'statistics' => $this->analyzeStatistics($content),
            'references' => $this->analyzeReferences($content),
        ];

        // Calculate scores (total: 12 points)
        $linkScore = $this->calculateLinkScore($details['external_links']);      // Up to 5 pts
        $citationScore = $this->calculateCitationScore($details['citations']);   // Up to 3 pts
        $statsScore = $this->calculateStatsScore($details['statistics']);        // Up to 2 pts
        $refScore = $this->calculateReferenceScore($details['references']);      // Up to 2 pts

        $totalScore = $linkScore + $citationScore + $statsScore + $refScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'external_links' => $linkScore,
                    'citations' => $citationScore,
                    'statistics' => $statsScore,
                    'references' => $refScore,
                ],
            ]),
        ];
    }

    public function getMaxScore(): float
    {
        return self::MAX_SCORE;
    }

    public function getName(): string
    {
        return 'Citations & Sources';
    }

    private function extractDomain(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parsed = parse_url($url);

        return $parsed['host'] ?? null;
    }

    private function analyzeExternalLinks(string $content, ?string $baseDomain): array
    {
        $result = [
            'total_external' => 0,
            'authoritative_count' => 0,
            'reputable_count' => 0,
            'authoritative_domains' => [],
            'all_external_domains' => [],
        ];

        // Find all links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        foreach ($matches[1] as $href) {
            // Skip internal, anchor, and javascript links
            if (str_starts_with($href, '#') ||
                str_starts_with($href, 'javascript:') ||
                str_starts_with($href, 'mailto:') ||
                str_starts_with($href, 'tel:')) {
                continue;
            }

            $linkDomain = $this->extractDomain($href);
            if (! $linkDomain) {
                continue;
            }

            // Skip if same domain
            if ($baseDomain && str_contains($linkDomain, str_replace('www.', '', $baseDomain))) {
                continue;
            }

            $result['total_external']++;
            $result['all_external_domains'][] = $linkDomain;

            // Check if authoritative
            foreach (self::AUTHORITATIVE_DOMAINS as $authDomain) {
                if (str_contains($linkDomain, $authDomain) || str_ends_with($linkDomain, $authDomain)) {
                    $result['authoritative_count']++;
                    $result['authoritative_domains'][] = $linkDomain;
                    break;
                }
            }

            // Check if reputable
            foreach (self::REPUTABLE_DOMAINS as $repDomain) {
                if (str_contains($linkDomain, $repDomain)) {
                    $result['reputable_count']++;
                    break;
                }
            }
        }

        $result['authoritative_domains'] = array_unique(array_slice($result['authoritative_domains'], 0, 10));
        $result['all_external_domains'] = array_unique($result['all_external_domains']);
        $result['unique_domains'] = count($result['all_external_domains']);

        return $result;
    }

    private function analyzeCitations(string $content): array
    {
        $result = [
            'has_inline_citations' => false,
            'citation_count' => 0,
            'citation_patterns' => [],
            'has_blockquotes' => false,
            'blockquote_count' => 0,
        ];

        // Check for inline citation patterns
        $citationPatterns = [
            '/according to\s+[A-Z][a-z]+/i' => 'according to',
            '/\((?:Source|Ref|Citation):[^)]+\)/i' => 'source reference',
            '/\[\d+\]/' => 'numbered citation',
            '/\[(?:Source|Ref|Citation)\]/i' => 'bracketed citation',
            '/(?:study|research|report)\s+(?:by|from)\s+[A-Z]/i' => 'study reference',
            '/(?:published|reported)\s+(?:in|by)\s+[A-Z]/i' => 'publication reference',
            '/data\s+(?:from|shows|indicates)/i' => 'data reference',
        ];

        foreach ($citationPatterns as $pattern => $type) {
            if (preg_match_all($pattern, $content, $matches)) {
                $result['has_inline_citations'] = true;
                $result['citation_count'] += count($matches[0]);
                $result['citation_patterns'][] = $type;
            }
        }

        $result['citation_patterns'] = array_unique($result['citation_patterns']);

        // Check for blockquotes
        preg_match_all('/<blockquote[^>]*>.*?<\/blockquote>/is', $content, $blockquotes);
        $result['blockquote_count'] = count($blockquotes[0]);
        $result['has_blockquotes'] = $result['blockquote_count'] > 0;

        return $result;
    }

    private function analyzeStatistics(string $content): array
    {
        $result = [
            'has_statistics' => false,
            'statistic_count' => 0,
            'has_percentages' => false,
            'has_numbers_with_context' => false,
            'examples' => [],
        ];

        $text = strip_tags($content);

        // Check for percentages
        if (preg_match_all('/\d+(?:\.\d+)?%/', $text, $matches)) {
            $result['has_percentages'] = true;
            $result['statistic_count'] += count($matches[0]);
            $result['has_statistics'] = true;
        }

        // Check for numbers with context (e.g., "5 million users", "$10 billion")
        $numberPatterns = [
            '/\$[\d,]+(?:\.\d+)?(?:\s*(?:million|billion|trillion))?/i',
            '/[\d,]+(?:\.\d+)?\s*(?:million|billion|trillion)/i',
            '/(?:over|more than|approximately|about|nearly)\s*[\d,]+/i',
            '/[\d,]+\s*(?:users|customers|people|companies|countries)/i',
        ];

        foreach ($numberPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $result['has_numbers_with_context'] = true;
                $result['statistic_count'] += count($matches[0]);
                $result['has_statistics'] = true;
                $result['examples'] = array_merge($result['examples'], array_slice($matches[0], 0, 3));
            }
        }

        $result['examples'] = array_unique(array_slice($result['examples'], 0, 5));

        return $result;
    }

    private function analyzeReferences(string $content): array
    {
        $result = [
            'has_reference_section' => false,
            'has_bibliography' => false,
            'has_footnotes' => false,
            'reference_links_count' => 0,
        ];

        // Check for reference/bibliography sections
        $refPatterns = [
            '/<h[2-4][^>]*>[^<]*(?:References|Bibliography|Sources|Citations|Works Cited)[^<]*<\/h[2-4]>/i',
            '/class=["\'][^"\']*(?:references|bibliography|sources|footnotes)[^"\']*["\']/',
            '/id=["\'](?:references|bibliography|sources|footnotes)["\']/',
        ];

        foreach ($refPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_reference_section'] = true;
                break;
            }
        }

        // Check for bibliography
        if (preg_match('/bibliography/i', $content)) {
            $result['has_bibliography'] = true;
        }

        // Check for footnotes
        if (preg_match('/class=["\'][^"\']*footnote/i', $content) ||
            preg_match('/<sup[^>]*>\s*\[\d+\]\s*<\/sup>/i', $content)) {
            $result['has_footnotes'] = true;
        }

        // Count links in reference section (simplified check)
        if ($result['has_reference_section']) {
            // Estimate reference links
            preg_match_all('/<li[^>]*>.*?<a[^>]+href=["\']https?:\/\//i', $content, $refLinks);
            $result['reference_links_count'] = count($refLinks[0]);
        }

        return $result;
    }

    private function calculateLinkScore(array $links): float
    {
        $score = 0;

        // Base score for having external links
        if ($links['total_external'] >= 3) {
            $score += 1;
        }

        // Authoritative sources (main scoring)
        if ($links['authoritative_count'] >= 3) {
            $score += 2.5;
        } elseif ($links['authoritative_count'] >= 1) {
            $score += 1.5;
        }

        // Reputable sources
        if ($links['reputable_count'] >= 2) {
            $score += 1;
        }

        // Diversity of sources
        if ($links['unique_domains'] >= 5) {
            $score += 0.5;
        }

        return min(5, $score);
    }

    private function calculateCitationScore(array $citations): float
    {
        $score = 0;

        if ($citations['has_inline_citations']) {
            $score += 1.5;
        }

        if ($citations['citation_count'] >= 3) {
            $score += 1;
        }

        if ($citations['has_blockquotes']) {
            $score += 0.5;
        }

        return min(3, $score);
    }

    private function calculateStatsScore(array $stats): float
    {
        $score = 0;

        if ($stats['has_statistics']) {
            $score += 1;
        }

        if ($stats['statistic_count'] >= 3) {
            $score += 0.5;
        }

        if ($stats['has_numbers_with_context']) {
            $score += 0.5;
        }

        return min(2, $score);
    }

    private function calculateReferenceScore(array $refs): float
    {
        $score = 0;

        if ($refs['has_reference_section']) {
            $score += 1;
        }

        if ($refs['has_footnotes'] || $refs['has_bibliography']) {
            $score += 0.5;
        }

        if ($refs['reference_links_count'] >= 3) {
            $score += 0.5;
        }

        return min(2, $score);
    }
}
