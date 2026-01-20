<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on machine-readable formatting.
 *
 * Measures:
 * - Schema.org structured data (JSON-LD, microdata)
 * - Semantic HTML elements
 * - FAQ presence and formatting
 * - Meta tags and Open Graph
 * - llms.txt file presence and quality
 */
class MachineReadableScorer implements ScorerInterface
{
    private const MAX_SCORE = 15;

    public function score(string $content, array $context = []): array
    {
        $details = [
            'schema' => $this->analyzeSchema($content),
            'semantic_html' => $this->analyzeSemanticHtml($content),
            'faq' => $this->analyzeFaq($content),
            'meta' => $this->analyzeMeta($content),
            'llms_txt' => $this->analyzeLlmsTxt($context['url'] ?? null),
        ];

        // Calculate scores (total: 15 points)
        $schemaScore = $this->calculateSchemaScore($details['schema']);           // Up to 5 pts
        $semanticScore = $this->calculateSemanticScore($details['semantic_html']); // Up to 3 pts
        $faqScore = $this->calculateFaqScore($details['faq']);                     // Up to 3 pts
        $metaScore = $this->calculateMetaScore($details['meta']);                  // Up to 2 pts
        $llmsTxtScore = $this->calculateLlmsTxtScore($details['llms_txt']);        // Up to 2 pts

        $totalScore = $schemaScore + $semanticScore + $faqScore + $metaScore + $llmsTxtScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'schema' => $schemaScore,
                    'semantic_html' => $semanticScore,
                    'faq' => $faqScore,
                    'meta' => $metaScore,
                    'llms_txt' => $llmsTxtScore,
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
        return 'Machine-Readable Formatting';
    }

    private function analyzeSchema(string $content): array
    {
        $schemas = [];

        // JSON-LD
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $content, $jsonLdMatches);

        foreach ($jsonLdMatches[1] as $jsonLd) {
            $decoded = json_decode($jsonLd, true);
            if ($decoded) {
                $schemas[] = [
                    'type' => 'json-ld',
                    'schema_type' => $decoded['@type'] ?? 'Unknown',
                    'valid' => true,
                ];
            }
        }

        // Microdata
        preg_match_all('/itemtype=["\']https?:\/\/schema\.org\/([^"\']+)["\']/', $content, $microdataMatches);
        foreach (array_unique($microdataMatches[1]) as $type) {
            $schemas[] = [
                'type' => 'microdata',
                'schema_type' => $type,
                'valid' => true,
            ];
        }

        // RDFa
        preg_match_all('/typeof=["\']([^"\']+)["\']/', $content, $rdfaMatches);
        foreach (array_unique($rdfaMatches[1]) as $type) {
            $schemas[] = [
                'type' => 'rdfa',
                'schema_type' => $type,
                'valid' => true,
            ];
        }

        // Check for common valuable schema types
        $schemaTypes = array_column($schemas, 'schema_type');
        $valuableTypes = ['Article', 'FAQPage', 'HowTo', 'Product', 'Organization',
            'Person', 'LocalBusiness', 'BreadcrumbList', 'WebPage', 'BlogPosting'];

        $hasValuableSchema = ! empty(array_intersect($schemaTypes, $valuableTypes));

        return [
            'found' => count($schemas),
            'schemas' => $schemas,
            'schema_types' => $schemaTypes,
            'has_valuable_schema' => $hasValuableSchema,
            'has_json_ld' => count($jsonLdMatches[1]) > 0,
        ];
    }

    private function analyzeSemanticHtml(string $content): array
    {
        $elements = [
            'article' => $this->countTag($content, 'article'),
            'section' => $this->countTag($content, 'section'),
            'aside' => $this->countTag($content, 'aside'),
            'nav' => $this->countTag($content, 'nav'),
            'header' => $this->countTag($content, 'header'),
            'footer' => $this->countTag($content, 'footer'),
            'main' => $this->countTag($content, 'main'),
            'figure' => $this->countTag($content, 'figure'),
            'figcaption' => $this->countTag($content, 'figcaption'),
            'time' => $this->countTag($content, 'time'),
            'address' => $this->countTag($content, 'address'),
            'mark' => $this->countTag($content, 'mark'),
            'details' => $this->countTag($content, 'details'),
            'summary' => $this->countTag($content, 'summary'),
        ];

        $totalSemanticElements = array_sum($elements);
        $usedElements = array_filter($elements, fn ($count) => $count > 0);

        // Check for proper image alt tags
        preg_match_all('/<img[^>]+>/i', $content, $imgMatches);
        $totalImages = count($imgMatches[0]);
        $imagesWithAlt = 0;
        foreach ($imgMatches[0] as $img) {
            if (preg_match('/alt=["\'][^"\']+["\']/', $img)) {
                $imagesWithAlt++;
            }
        }

        // Check for proper link context
        preg_match_all('/<a[^>]+>(.*?)<\/a>/is', $content, $linkMatches);
        $meaningfulLinks = 0;
        foreach ($linkMatches[1] as $linkText) {
            $text = strtolower(trim(strip_tags($linkText)));
            if (! in_array($text, ['click here', 'read more', 'here', 'link', 'more'])) {
                $meaningfulLinks++;
            }
        }

        return [
            'elements' => $elements,
            'total_semantic_elements' => $totalSemanticElements,
            'unique_elements_used' => count($usedElements),
            'images' => [
                'total' => $totalImages,
                'with_alt' => $imagesWithAlt,
                'alt_coverage' => $totalImages > 0 ? round($imagesWithAlt / $totalImages * 100, 1) : 100,
            ],
            'links' => [
                'total' => count($linkMatches[0]),
                'meaningful' => $meaningfulLinks,
            ],
        ];
    }

    private function analyzeFaq(string $content): array
    {
        $faqPatterns = [
            'has_faq_schema' => false,
            'has_faq_section' => false,
            'question_count' => 0,
            'questions' => [],
        ];

        // Check for FAQPage schema
        if (preg_match('/FAQPage/i', $content)) {
            $faqPatterns['has_faq_schema'] = true;
        }

        // Check for FAQ section headers
        if (preg_match('/<h[1-6][^>]*>[^<]*(FAQ|Frequently Asked|Common Questions)[^<]*<\/h[1-6]>/i', $content)) {
            $faqPatterns['has_faq_section'] = true;
        }

        // Look for question patterns in headings
        preg_match_all('/<h[2-6][^>]*>([^<]*\?)<\/h[2-6]>/i', $content, $questionMatches);
        $faqPatterns['question_count'] += count($questionMatches[1]);
        $faqPatterns['questions'] = array_merge(
            $faqPatterns['questions'],
            array_map(fn ($q) => $this->truncate(strip_tags($q), 100), $questionMatches[1])
        );

        // Look for details/summary elements (accordion FAQ)
        preg_match_all('/<summary[^>]*>(.*?)<\/summary>/is', $content, $summaryMatches);
        foreach ($summaryMatches[1] as $summary) {
            $text = strip_tags($summary);
            if (str_contains($text, '?')) {
                $faqPatterns['question_count']++;
                $faqPatterns['questions'][] = $this->truncate($text, 100);
            }
        }

        // Look for Q&A patterns in text
        preg_match_all('/(?:^|\n)\s*(?:Q:|Question:)\s*([^\n]+\?)/i', strip_tags($content), $qaMatches);
        $faqPatterns['question_count'] += count($qaMatches[1]);

        $faqPatterns['questions'] = array_unique(array_slice($faqPatterns['questions'], 0, 10));

        return $faqPatterns;
    }

    private function analyzeMeta(string $content): array
    {
        $meta = [
            'title' => null,
            'description' => null,
            'canonical' => null,
            'robots' => null,
            'og_tags' => [],
            'twitter_tags' => [],
        ];

        // Title tag
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $titleMatch)) {
            $meta['title'] = trim($titleMatch[1]);
        }

        // Meta description
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/', $content, $descMatch)) {
            $meta['description'] = $descMatch[1];
        }
        if (! $meta['description'] && preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/', $content, $descMatch)) {
            $meta['description'] = $descMatch[1];
        }

        // Canonical
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/', $content, $canonicalMatch)) {
            $meta['canonical'] = $canonicalMatch[1];
        }

        // Robots
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)["\']/', $content, $robotsMatch)) {
            $meta['robots'] = $robotsMatch[1];
        }

        // Open Graph
        preg_match_all('/<meta[^>]+property=["\']og:([^"\']+)["\'][^>]+content=["\']([^"\']+)["\']/', $content, $ogMatches, PREG_SET_ORDER);
        foreach ($ogMatches as $match) {
            $meta['og_tags'][$match[1]] = $match[2];
        }

        // Twitter cards
        preg_match_all('/<meta[^>]+name=["\']twitter:([^"\']+)["\'][^>]+content=["\']([^"\']+)["\']/', $content, $twitterMatches, PREG_SET_ORDER);
        foreach ($twitterMatches as $match) {
            $meta['twitter_tags'][$match[1]] = $match[2];
        }

        return [
            'has_title' => ! empty($meta['title']),
            'has_description' => ! empty($meta['description']),
            'has_canonical' => ! empty($meta['canonical']),
            'has_og' => ! empty($meta['og_tags']),
            'has_twitter' => ! empty($meta['twitter_tags']),
            'title_length' => strlen($meta['title'] ?? ''),
            'description_length' => strlen($meta['description'] ?? ''),
            'details' => $meta,
        ];
    }

    private function calculateSchemaScore(array $schema): float
    {
        // Up to 5 points
        $score = 0;

        if ($schema['found'] > 0) {
            $score += 2;
        }

        if ($schema['has_json_ld']) {
            $score += 1;
        }

        if ($schema['has_valuable_schema']) {
            $score += 2;
        }

        return min(5, $score);
    }

    private function calculateSemanticScore(array $semantic): float
    {
        // Up to 3 points
        $score = 0;

        // Uses semantic elements
        if ($semantic['unique_elements_used'] >= 3) {
            $score += 1.5;
        } elseif ($semantic['unique_elements_used'] >= 1) {
            $score += 0.5;
        }

        // Good image alt coverage
        if ($semantic['images']['alt_coverage'] >= 90) {
            $score += 0.75;
        }

        // Meaningful link text
        $linkTotal = $semantic['links']['total'];
        if ($linkTotal > 0 && $semantic['links']['meaningful'] / $linkTotal >= 0.8) {
            $score += 0.75;
        }

        return min(3, $score);
    }

    private function calculateFaqScore(array $faq): float
    {
        // Up to 3 points
        $score = 0;

        if ($faq['has_faq_schema']) {
            $score += 1.5;
        }

        if ($faq['has_faq_section']) {
            $score += 0.75;
        }

        if ($faq['question_count'] >= 3) {
            $score += 0.75;
        }

        return min(3, $score);
    }

    private function calculateMetaScore(array $meta): float
    {
        // Up to 2 points
        $score = 0;

        // Has essential meta
        if ($meta['has_title'] && $meta['has_description']) {
            $score += 1;
        }

        // Has social meta
        if ($meta['has_og'] || $meta['has_twitter']) {
            $score += 1;
        }

        return min(2, $score);
    }

    private function countTag(string $content, string $tag): int
    {
        preg_match_all("/<{$tag}[^>]*>/i", $content, $matches);

        return count($matches[0]);
    }

    private function truncate(string $text, int $length): string
    {
        $text = trim($text);
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }

    /**
     * Analyze the presence of llms.txt file.
     *
     * Note: This checks for llms.txt references in the HTML content rather than
     * making a separate HTTP request, to avoid blocking the scan.
     * A full llms.txt fetch can be done as a separate background job if needed.
     */
    private function analyzeLlmsTxt(?string $url): array
    {
        $result = [
            'exists' => false,
            'url' => null,
            'content_length' => 0,
            'has_description' => false,
            'has_pages' => false,
            'has_sitemap_reference' => false,
            'has_contact_info' => false,
            'quality_score' => 0,
            'error' => null,
        ];

        if (empty($url)) {
            $result['error'] = 'No URL provided';
            return $result;
        }

        // Build the expected llms.txt URL for display purposes
        $parsed = parse_url($url);
        if (! empty($parsed['scheme']) && ! empty($parsed['host'])) {
            $result['url'] = $parsed['scheme'].'://'.$parsed['host'].'/llms.txt';
        }

        // Note: Actual llms.txt content check requires a separate HTTP request.
        // This is left as a recommendation rather than an automatic check to avoid
        // adding latency to scans. Users can manually verify llms.txt exists.
        $result['error'] = 'Manual verification recommended';

        return $result;
    }

    /**
     * Calculate score for llms.txt presence and quality.
     */
    private function calculateLlmsTxtScore(array $llmsTxt): float
    {
        // Up to 2 points
        $score = 0;

        // File exists
        if ($llmsTxt['exists']) {
            $score += 1;

            // Quality bonus based on content
            if ($llmsTxt['quality_score'] >= 60) {
                $score += 1;
            } elseif ($llmsTxt['quality_score'] >= 40) {
                $score += 0.5;
            }
        }

        return min(2, $score);
    }
}
