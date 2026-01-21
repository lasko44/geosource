<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on multimedia richness.
 *
 * AGENCY TIER FEATURE
 *
 * Measures:
 * - Image presence and optimization
 * - Video content
 * - Tables and data visualization
 * - Infographics and diagrams
 */
class MultimediaScorer implements ScorerInterface
{
    private const MAX_SCORE = 10;

    public function score(string $content, array $context = []): array
    {
        $details = [
            'images' => $this->analyzeImages($content),
            'videos' => $this->analyzeVideos($content),
            'tables' => $this->analyzeTables($content),
            'visual_elements' => $this->analyzeVisualElements($content),
        ];

        // Calculate scores (total: 10 points)
        $imageScore = $this->calculateImageScore($details['images']);          // Up to 4 pts
        $videoScore = $this->calculateVideoScore($details['videos']);          // Up to 2 pts
        $tableScore = $this->calculateTableScore($details['tables']);          // Up to 2 pts
        $visualScore = $this->calculateVisualScore($details['visual_elements']); // Up to 2 pts

        $totalScore = $imageScore + $videoScore + $tableScore + $visualScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'images' => $imageScore,
                    'videos' => $videoScore,
                    'tables' => $tableScore,
                    'visual_elements' => $visualScore,
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
        return 'Multimedia Content';
    }

    private function analyzeImages(string $content): array
    {
        $result = [
            'total_images' => 0,
            'images_with_alt' => 0,
            'images_with_title' => 0,
            'images_with_caption' => 0,
            'has_featured_image' => false,
            'has_schema_images' => false,
            'alt_quality' => 'none',
            'lazy_loaded' => 0,
        ];

        // Find all images
        preg_match_all('/<img[^>]+>/i', $content, $imgMatches);
        $result['total_images'] = count($imgMatches[0]);

        foreach ($imgMatches[0] as $img) {
            // Check for alt text
            if (preg_match('/alt=["\']([^"\']+)["\']/', $img, $altMatch)) {
                $altText = trim($altMatch[1]);
                if (! empty($altText) && strlen($altText) > 3) {
                    $result['images_with_alt']++;
                }
            }

            // Check for title
            if (preg_match('/title=["\']([^"\']+)["\']/', $img)) {
                $result['images_with_title']++;
            }

            // Check for lazy loading
            if (preg_match('/loading=["\']lazy["\']/', $img) ||
                preg_match('/data-src=/', $img)) {
                $result['lazy_loaded']++;
            }
        }

        // Check for figure/figcaption (proper image captioning)
        preg_match_all('/<figure[^>]*>.*?<img.*?<figcaption/is', $content, $figureMatches);
        $result['images_with_caption'] = count($figureMatches[0]);

        // Check for featured image patterns
        if (preg_match('/class=["\'][^"\']*(?:featured|hero|banner|header)[_-]?image[^"\']*["\']/', $content) ||
            preg_match('/property=["\']og:image["\']/', $content)) {
            $result['has_featured_image'] = true;
        }

        // Check for schema image
        if (preg_match('/"image"\s*:\s*["\{\[]/', $content)) {
            $result['has_schema_images'] = true;
        }

        // Calculate alt quality
        if ($result['total_images'] > 0) {
            $altRatio = $result['images_with_alt'] / $result['total_images'];
            $result['alt_quality'] = match (true) {
                $altRatio >= 0.95 => 'excellent',
                $altRatio >= 0.8 => 'good',
                $altRatio >= 0.5 => 'fair',
                $altRatio > 0 => 'poor',
                default => 'none',
            };
            $result['alt_coverage'] = round($altRatio * 100, 1);
        }

        return $result;
    }

    private function analyzeVideos(string $content): array
    {
        $result = [
            'has_video' => false,
            'video_count' => 0,
            'video_types' => [],
            'has_video_schema' => false,
            'embedded_platforms' => [],
        ];

        // Check for video elements
        preg_match_all('/<video[^>]*>/i', $content, $videoTags);
        $result['video_count'] += count($videoTags[0]);

        // Check for YouTube embeds
        if (preg_match('/youtube\.com\/embed|youtu\.be/i', $content)) {
            $result['video_count']++;
            $result['embedded_platforms'][] = 'youtube';
        }

        // Check for Vimeo embeds
        if (preg_match('/player\.vimeo\.com/i', $content)) {
            $result['video_count']++;
            $result['embedded_platforms'][] = 'vimeo';
        }

        // Check for Wistia
        if (preg_match('/wistia/i', $content)) {
            $result['video_count']++;
            $result['embedded_platforms'][] = 'wistia';
        }

        // Check for generic iframes that might be videos
        if (preg_match('/<iframe[^>]+(?:video|embed)[^>]*>/i', $content)) {
            $result['video_count']++;
        }

        $result['has_video'] = $result['video_count'] > 0;
        $result['embedded_platforms'] = array_unique($result['embedded_platforms']);

        // Check for video schema
        if (preg_match('/"@type"\s*:\s*"VideoObject"/i', $content)) {
            $result['has_video_schema'] = true;
        }

        return $result;
    }

    private function analyzeTables(string $content): array
    {
        $result = [
            'has_tables' => false,
            'table_count' => 0,
            'tables_with_headers' => 0,
            'tables_with_caption' => 0,
            'avg_columns' => 0,
            'avg_rows' => 0,
            'has_comparison_table' => false,
        ];

        // Find all tables
        preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $content, $tables);
        $result['table_count'] = count($tables[0]);
        $result['has_tables'] = $result['table_count'] > 0;

        $totalCols = 0;
        $totalRows = 0;

        foreach ($tables[0] as $table) {
            // Check for headers
            if (preg_match('/<th[^>]*>/i', $table)) {
                $result['tables_with_headers']++;
            }

            // Check for caption
            if (preg_match('/<caption[^>]*>/i', $table)) {
                $result['tables_with_caption']++;
            }

            // Count rows
            preg_match_all('/<tr[^>]*>/i', $table, $rows);
            $totalRows += count($rows[0]);

            // Count columns (from first row)
            if (preg_match('/<tr[^>]*>(.*?)<\/tr>/is', $table, $firstRow)) {
                preg_match_all('/<t[hd][^>]*>/i', $firstRow[1], $cols);
                $totalCols += count($cols[0]);
            }

            // Check for comparison table patterns
            if (preg_match('/class=["\'][^"\']*comparison/i', $table) ||
                preg_match('/vs\.?|versus|compare/i', $table)) {
                $result['has_comparison_table'] = true;
            }
        }

        if ($result['table_count'] > 0) {
            $result['avg_columns'] = round($totalCols / $result['table_count'], 1);
            $result['avg_rows'] = round($totalRows / $result['table_count'], 1);
        }

        return $result;
    }

    private function analyzeVisualElements(string $content): array
    {
        $result = [
            'has_diagrams' => false,
            'has_infographics' => false,
            'has_charts' => false,
            'has_icons' => false,
            'has_code_blocks' => false,
            'has_callouts' => false,
            'visual_variety' => 0,
        ];

        // Check for diagrams/flowcharts
        if (preg_match('/class=["\'][^"\']*(?:diagram|flowchart|flow-chart)/i', $content) ||
            preg_match('/<svg[^>]*>.*?(?:arrow|path|line)/is', $content)) {
            $result['has_diagrams'] = true;
        }

        // Check for infographics
        if (preg_match('/class=["\'][^"\']*infographic/i', $content) ||
            preg_match('/alt=["\'][^"\']*infographic/i', $content)) {
            $result['has_infographics'] = true;
        }

        // Check for charts (chart.js, d3, highcharts, etc.)
        if (preg_match('/class=["\'][^"\']*chart/i', $content) ||
            preg_match('/<canvas[^>]*(?:chart|graph)/i', $content) ||
            preg_match('/(?:chart|graph)\.js|highcharts|d3\.js/i', $content)) {
            $result['has_charts'] = true;
        }

        // Check for icons
        if (preg_match('/class=["\'][^"\']*(?:icon|fa-|bi-|material-icons)/i', $content) ||
            preg_match('/<svg[^>]*class=["\'][^"\']*icon/i', $content)) {
            $result['has_icons'] = true;
        }

        // Check for code blocks
        if (preg_match('/<pre[^>]*>.*?<code/is', $content) ||
            preg_match('/<code[^>]*class=["\'][^"\']*(?:language-|hljs)/i', $content)) {
            $result['has_code_blocks'] = true;
        }

        // Check for callouts/alerts/highlights
        if (preg_match('/class=["\'][^"\']*(?:callout|alert|notice|highlight|tip|warning|info-box)/i', $content)) {
            $result['has_callouts'] = true;
        }

        // Calculate visual variety
        $result['visual_variety'] = array_sum([
            $result['has_diagrams'] ? 1 : 0,
            $result['has_infographics'] ? 1 : 0,
            $result['has_charts'] ? 1 : 0,
            $result['has_icons'] ? 1 : 0,
            $result['has_code_blocks'] ? 1 : 0,
            $result['has_callouts'] ? 1 : 0,
        ]);

        return $result;
    }

    private function calculateImageScore(array $images): float
    {
        $score = 0;

        // Has images
        if ($images['total_images'] >= 3) {
            $score += 1.5;
        } elseif ($images['total_images'] >= 1) {
            $score += 1;
        }

        // Alt text quality
        $score += match ($images['alt_quality']) {
            'excellent' => 1.5,
            'good' => 1,
            'fair' => 0.5,
            default => 0,
        };

        // Proper captioning
        if ($images['images_with_caption'] > 0) {
            $score += 0.5;
        }

        // Schema/featured image
        if ($images['has_featured_image'] || $images['has_schema_images']) {
            $score += 0.5;
        }

        return min(4, $score);
    }

    private function calculateVideoScore(array $videos): float
    {
        $score = 0;

        if ($videos['has_video']) {
            $score += 1.5;

            if ($videos['has_video_schema']) {
                $score += 0.5;
            }
        }

        return min(2, $score);
    }

    private function calculateTableScore(array $tables): float
    {
        $score = 0;

        if ($tables['has_tables']) {
            $score += 1;

            // Well-structured tables
            if ($tables['tables_with_headers'] > 0) {
                $score += 0.5;
            }

            // Comparison table (high value for AI)
            if ($tables['has_comparison_table']) {
                $score += 0.5;
            }
        }

        return min(2, $score);
    }

    private function calculateVisualScore(array $visuals): float
    {
        $score = 0;

        // Reward visual variety
        if ($visuals['visual_variety'] >= 4) {
            $score += 2;
        } elseif ($visuals['visual_variety'] >= 2) {
            $score += 1.5;
        } elseif ($visuals['visual_variety'] >= 1) {
            $score += 1;
        }

        return min(2, $score);
    }
}
