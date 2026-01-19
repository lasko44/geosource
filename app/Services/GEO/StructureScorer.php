<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on structured knowledge.
 *
 * Measures:
 * - Presence and proper use of headings (h1-h6)
 * - Lists (ordered and unordered)
 * - Section count and organization
 * - Heading hierarchy (proper nesting)
 */
class StructureScorer implements ScorerInterface
{
    private const MAX_SCORE = 20;

    public function score(string $content, array $context = []): array
    {
        $details = [
            'headings' => $this->analyzeHeadings($content),
            'lists' => $this->analyzeLists($content),
            'sections' => $this->analyzeSections($content),
            'hierarchy' => $this->analyzeHierarchy($content),
        ];

        // Calculate scores for each component
        $headingScore = $this->calculateHeadingScore($details['headings']);
        $listScore = $this->calculateListScore($details['lists']);
        $sectionScore = $this->calculateSectionScore($details['sections']);
        $hierarchyScore = $this->calculateHierarchyScore($details['hierarchy']);

        $totalScore = $headingScore + $listScore + $sectionScore + $hierarchyScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'headings' => $headingScore,
                    'lists' => $listScore,
                    'sections' => $sectionScore,
                    'hierarchy' => $hierarchyScore,
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
        return 'Structured Knowledge';
    }

    private function analyzeHeadings(string $content): array
    {
        $headings = [];

        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $content, $matches);
            $headings["h{$i}"] = [
                'count' => count($matches[1]),
                'texts' => array_map(fn ($h) => $this->truncate(strip_tags($h), 80), $matches[1]),
            ];
        }

        $headings['total'] = array_sum(array_column($headings, 'count'));

        return $headings;
    }

    private function analyzeLists(string $content): array
    {
        // Unordered lists
        preg_match_all('/<ul[^>]*>(.*?)<\/ul>/is', $content, $ulMatches);
        $ulCount = count($ulMatches[0]);
        $ulItems = 0;
        foreach ($ulMatches[1] as $ul) {
            preg_match_all('/<li[^>]*>/i', $ul, $liMatches);
            $ulItems += count($liMatches[0]);
        }

        // Ordered lists
        preg_match_all('/<ol[^>]*>(.*?)<\/ol>/is', $content, $olMatches);
        $olCount = count($olMatches[0]);
        $olItems = 0;
        foreach ($olMatches[1] as $ol) {
            preg_match_all('/<li[^>]*>/i', $ol, $liMatches);
            $olItems += count($liMatches[0]);
        }

        // Definition lists
        preg_match_all('/<dl[^>]*>(.*?)<\/dl>/is', $content, $dlMatches);
        $dlCount = count($dlMatches[0]);

        return [
            'unordered' => ['count' => $ulCount, 'items' => $ulItems],
            'ordered' => ['count' => $olCount, 'items' => $olItems],
            'definition' => ['count' => $dlCount],
            'total_lists' => $ulCount + $olCount + $dlCount,
            'total_items' => $ulItems + $olItems,
        ];
    }

    private function analyzeSections(string $content): array
    {
        // Count semantic sections
        preg_match_all('/<(section|article|aside|nav|header|footer|main)[^>]*>/i', $content, $matches);
        $semanticSections = count($matches[0]);

        // Estimate sections by heading distribution
        $headingCount = 0;
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>/i", $content, $hMatches);
            $headingCount += count($hMatches[0]);
        }

        // Count paragraphs
        preg_match_all('/<p[^>]*>/i', $content, $pMatches);
        $paragraphCount = count($pMatches[0]);

        return [
            'semantic_sections' => $semanticSections,
            'implied_sections' => $headingCount,
            'paragraphs' => $paragraphCount,
            'content_density' => $headingCount > 0 ? round($paragraphCount / $headingCount, 2) : 0,
        ];
    }

    private function analyzeHierarchy(string $content): array
    {
        $levels = [];
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>/i", $content, $matches);
            if (count($matches[0]) > 0) {
                $levels[] = $i;
            }
        }

        $isProperlyNested = true;
        $violations = [];

        // Check if heading levels skip (e.g., h1 -> h3 without h2)
        for ($i = 1; $i < count($levels); $i++) {
            if ($levels[$i] - $levels[$i - 1] > 1) {
                $isProperlyNested = false;
                $violations[] = "Skipped from h{$levels[$i - 1]} to h{$levels[$i]}";
            }
        }

        // Check if there's exactly one h1
        preg_match_all('/<h1[^>]*>/i', $content, $h1Matches);
        $h1Count = count($h1Matches[0]);
        $hasProperH1 = $h1Count === 1;

        if ($h1Count === 0) {
            $violations[] = 'Missing h1 heading';
        } elseif ($h1Count > 1) {
            $violations[] = "Multiple h1 headings ({$h1Count} found)";
        }

        return [
            'levels_used' => $levels,
            'properly_nested' => $isProperlyNested,
            'has_proper_h1' => $hasProperH1,
            'violations' => $violations,
            'depth' => count($levels),
        ];
    }

    private function calculateHeadingScore(array $headings): float
    {
        // Up to 6 points for headings
        $score = 0;

        // Has h1
        if ($headings['h1']['count'] === 1) {
            $score += 2;
        }

        // Has subheadings
        $subheadingCount = $headings['h2']['count'] + $headings['h3']['count'];
        $score += min(4, $subheadingCount);

        return $score;
    }

    private function calculateListScore(array $lists): float
    {
        // Up to 5 points for lists
        $score = 0;

        // Has lists
        if ($lists['total_lists'] > 0) {
            $score += 2;
        }

        // Has multiple items
        if ($lists['total_items'] >= 3) {
            $score += 1.5;
        }
        if ($lists['total_items'] >= 6) {
            $score += 1.5;
        }

        return min(5, $score);
    }

    private function calculateSectionScore(array $sections): float
    {
        // Up to 4 points for sections
        $score = 0;

        // Uses semantic HTML
        if ($sections['semantic_sections'] > 0) {
            $score += 2;
        }

        // Has good content density (3-8 paragraphs per section)
        if ($sections['content_density'] >= 2 && $sections['content_density'] <= 10) {
            $score += 2;
        }

        return min(4, $score);
    }

    private function calculateHierarchyScore(array $hierarchy): float
    {
        // Up to 5 points for hierarchy
        $score = 0;

        // Has proper h1
        if ($hierarchy['has_proper_h1']) {
            $score += 2;
        }

        // Properly nested
        if ($hierarchy['properly_nested']) {
            $score += 2;
        }

        // Good depth (2-4 levels)
        if ($hierarchy['depth'] >= 2 && $hierarchy['depth'] <= 4) {
            $score += 1;
        }

        return min(5, $score);
    }

    private function truncate(string $text, int $length): string
    {
        $text = trim($text);
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }
}
