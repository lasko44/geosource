<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on freshness and recency signals.
 *
 * AGENCY TIER FEATURE
 *
 * Measures:
 * - Publication date presence
 * - Last modified indicators
 * - Content recency signals
 * - Temporal relevance
 */
class FreshnessScorer implements ScorerInterface
{
    private const MAX_SCORE = 10;

    public function score(string $content, array $context = []): array
    {
        $details = [
            'dates' => $this->analyzeDates($content),
            'update_signals' => $this->analyzeUpdateSignals($content),
            'temporal_references' => $this->analyzeTemporalReferences($content),
            'schema_dates' => $this->analyzeSchemaDate($content),
        ];

        // Calculate scores (total: 10 points)
        $dateScore = $this->calculateDateScore($details['dates']);              // Up to 4 pts
        $updateScore = $this->calculateUpdateScore($details['update_signals']); // Up to 3 pts
        $temporalScore = $this->calculateTemporalScore($details['temporal_references']); // Up to 2 pts
        $schemaScore = $this->calculateSchemaDateScore($details['schema_dates']); // Up to 1 pt

        $totalScore = $dateScore + $updateScore + $temporalScore + $schemaScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'dates' => $dateScore,
                    'update_signals' => $updateScore,
                    'temporal_references' => $temporalScore,
                    'schema_dates' => $schemaScore,
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
        return 'Content Freshness';
    }

    private function analyzeDates(string $content): array
    {
        $result = [
            'has_publish_date' => false,
            'has_modified_date' => false,
            'publish_date' => null,
            'modified_date' => null,
            'dates_found' => [],
            'most_recent_date' => null,
            'age_category' => 'unknown',
        ];

        // Check for time elements with datetime
        if (preg_match('/<time[^>]+datetime=["\']([^"\']+)["\']/', $content, $match)) {
            $result['has_publish_date'] = true;
            $result['dates_found'][] = $match[1];
            $result['publish_date'] = $this->parseDate($match[1]);
        }

        // Check for meta publish date
        $publishMetaPatterns = [
            '/<meta[^>]+property=["\']article:published_time["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+name=["\'](?:publish|publication)[_-]?date["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+name=["\']date["\'][^>]+content=["\']([^"\']+)["\']/',
        ];

        foreach ($publishMetaPatterns as $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $result['has_publish_date'] = true;
                $result['publish_date'] = $this->parseDate($match[1]);
                $result['dates_found'][] = $match[1];
                break;
            }
        }

        // Check for modified date meta
        $modifiedMetaPatterns = [
            '/<meta[^>]+property=["\']article:modified_time["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+name=["\'](?:last[_-]?)?modified["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+http-equiv=["\']last-modified["\'][^>]+content=["\']([^"\']+)["\']/',
        ];

        foreach ($modifiedMetaPatterns as $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $result['has_modified_date'] = true;
                $result['modified_date'] = $this->parseDate($match[1]);
                $result['dates_found'][] = $match[1];
                break;
            }
        }

        // Look for visible dates in content
        $visibleDatePatterns = [
            '/(?:published|posted|written)\s*(?:on)?\s*:?\s*(\w+\s+\d{1,2},?\s+\d{4})/i',
            '/(?:updated|modified|revised)\s*(?:on)?\s*:?\s*(\w+\s+\d{1,2},?\s+\d{4})/i',
            '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/',
            '/(\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
        ];

        foreach ($visibleDatePatterns as $pattern) {
            if (preg_match($pattern, strip_tags($content), $match)) {
                $parsed = $this->parseDate($match[1]);
                if ($parsed) {
                    $result['dates_found'][] = $match[1];
                    if (! $result['publish_date']) {
                        $result['has_publish_date'] = true;
                        $result['publish_date'] = $parsed;
                    }
                }
            }
        }

        // Determine most recent date and age category
        $dates = array_filter([$result['publish_date'], $result['modified_date']]);
        if (! empty($dates)) {
            $mostRecent = max($dates);
            $result['most_recent_date'] = date('Y-m-d', $mostRecent);
            $result['age_category'] = $this->categorizeAge($mostRecent);
        }

        return $result;
    }

    private function analyzeUpdateSignals(string $content): array
    {
        $result = [
            'has_update_notice' => false,
            'has_revision_history' => false,
            'has_changelog' => false,
            'update_phrases' => [],
        ];

        $text = strip_tags($content);

        // Check for update notices
        $updatePatterns = [
            '/(?:last\s+)?updated?\s*(?:on|:)/i',
            '/(?:editor\'?s?\s+)?note:\s*(?:this|updated)/i',
            '/this\s+(?:article|post|page)\s+(?:was\s+)?(?:last\s+)?updated/i',
            '/originally\s+published.*?updated/i',
        ];

        foreach ($updatePatterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $result['has_update_notice'] = true;
                $result['update_phrases'][] = trim($match[0]);
            }
        }

        // Check for revision history
        if (preg_match('/(?:revision|version)\s*history/i', $content) ||
            preg_match('/class=["\'][^"\']*revision/i', $content)) {
            $result['has_revision_history'] = true;
        }

        // Check for changelog
        if (preg_match('/changelog/i', $content) ||
            preg_match('/<h[2-4][^>]*>[^<]*change\s*log[^<]*<\/h[2-4]>/i', $content)) {
            $result['has_changelog'] = true;
        }

        $result['update_phrases'] = array_unique(array_slice($result['update_phrases'], 0, 3));

        return $result;
    }

    private function analyzeTemporalReferences(string $content): array
    {
        $result = [
            'has_year_references' => false,
            'current_year_mentioned' => false,
            'recent_year_mentioned' => false,
            'years_found' => [],
            'has_temporal_context' => false,
        ];

        $currentYear = (int) date('Y');
        $text = strip_tags($content);

        // Find year references
        if (preg_match_all('/\b(20[0-2]\d)\b/', $text, $matches)) {
            $years = array_unique($matches[1]);
            $result['has_year_references'] = true;
            $result['years_found'] = array_values($years);

            foreach ($years as $year) {
                $year = (int) $year;
                if ($year === $currentYear) {
                    $result['current_year_mentioned'] = true;
                } elseif ($year >= $currentYear - 1) {
                    $result['recent_year_mentioned'] = true;
                }
            }
        }

        // Check for temporal context phrases
        $temporalPatterns = [
            '/(?:as of|in)\s+\d{4}/i',
            '/(?:this|current)\s+year/i',
            '/(?:latest|recent|new)\s+(?:data|research|study|findings)/i',
            '/\d{4}\s+(?:update|edition|version)/i',
        ];

        foreach ($temporalPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $result['has_temporal_context'] = true;
                break;
            }
        }

        return $result;
    }

    private function analyzeSchemaDate(string $content): array
    {
        $result = [
            'has_date_published' => false,
            'has_date_modified' => false,
            'schema_dates' => [],
        ];

        // Check JSON-LD for dates
        if (preg_match('/"datePublished"\s*:\s*"([^"]+)"/', $content, $match)) {
            $result['has_date_published'] = true;
            $result['schema_dates']['published'] = $match[1];
        }

        if (preg_match('/"dateModified"\s*:\s*"([^"]+)"/', $content, $match)) {
            $result['has_date_modified'] = true;
            $result['schema_dates']['modified'] = $match[1];
        }

        // Check microdata
        if (preg_match('/itemprop=["\']datePublished["\'][^>]+content=["\']([^"\']+)["\']/', $content, $match)) {
            $result['has_date_published'] = true;
            $result['schema_dates']['published'] = $match[1];
        }

        if (preg_match('/itemprop=["\']dateModified["\'][^>]+content=["\']([^"\']+)["\']/', $content, $match)) {
            $result['has_date_modified'] = true;
            $result['schema_dates']['modified'] = $match[1];
        }

        return $result;
    }

    private function parseDate(string $dateStr): ?int
    {
        $timestamp = strtotime($dateStr);

        return $timestamp !== false ? $timestamp : null;
    }

    private function categorizeAge(int $timestamp): string
    {
        $daysSince = (time() - $timestamp) / 86400;

        return match (true) {
            $daysSince <= 30 => 'very_fresh',      // Within 1 month
            $daysSince <= 90 => 'fresh',           // Within 3 months
            $daysSince <= 180 => 'recent',         // Within 6 months
            $daysSince <= 365 => 'moderate',       // Within 1 year
            $daysSince <= 730 => 'aging',          // Within 2 years
            default => 'stale',                     // Over 2 years
        };
    }

    private function calculateDateScore(array $dates): float
    {
        $score = 0;

        // Has publication date
        if ($dates['has_publish_date']) {
            $score += 2;
        }

        // Has modification date (shows content is maintained)
        if ($dates['has_modified_date']) {
            $score += 1;
        }

        // Age-based scoring
        $score += match ($dates['age_category']) {
            'very_fresh' => 1,
            'fresh' => 0.75,
            'recent' => 0.5,
            'moderate' => 0.25,
            'aging', 'stale', 'unknown' => 0,
        };

        return min(4, $score);
    }

    private function calculateUpdateScore(array $updates): float
    {
        $score = 0;

        if ($updates['has_update_notice']) {
            $score += 2;
        }

        if ($updates['has_revision_history']) {
            $score += 0.5;
        }

        if ($updates['has_changelog']) {
            $score += 0.5;
        }

        return min(3, $score);
    }

    private function calculateTemporalScore(array $temporal): float
    {
        $score = 0;

        if ($temporal['current_year_mentioned']) {
            $score += 1.5;
        } elseif ($temporal['recent_year_mentioned']) {
            $score += 0.75;
        }

        if ($temporal['has_temporal_context']) {
            $score += 0.5;
        }

        return min(2, $score);
    }

    private function calculateSchemaDateScore(array $schema): float
    {
        $score = 0;

        if ($schema['has_date_published']) {
            $score += 0.5;
        }

        if ($schema['has_date_modified']) {
            $score += 0.5;
        }

        return min(1, $score);
    }
}
