<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Scores content based on topic authority.
 *
 * Uses pgvector to compute semantic similarity between this page
 * and other pages on the site. High similarity = topical focus.
 *
 * Also measures:
 * - Keyword consistency
 * - Topic depth indicators
 * - Internal linking density
 */
class AuthorityScorer implements ScorerInterface
{
    private const MAX_SCORE = 25;

    public function score(string $content, array $context = []): array
    {
        $teamId = $context['team_id'] ?? null;
        $url = $context['url'] ?? null;
        $embedding = $context['embedding'] ?? null;

        $details = [
            'topic_coherence' => $this->analyzeTopicCoherence($content),
            'keyword_density' => $this->analyzeKeywordDensity($content),
            'topic_depth' => $this->analyzeTopicDepth($content),
            'internal_links' => $this->analyzeInternalLinks($content, $url),
            'semantic_similarity' => null,
        ];

        // Calculate semantic similarity using pgvector if embedding available
        if ($embedding && $teamId) {
            $details['semantic_similarity'] = $this->calculateSemanticSimilarity(
                $embedding,
                $teamId,
                $context['document_id'] ?? null
            );
        }

        // Calculate scores
        $coherenceScore = $this->calculateCoherenceScore($details['topic_coherence']);
        $keywordScore = $this->calculateKeywordScore($details['keyword_density']);
        $depthScore = $this->calculateDepthScore($details['topic_depth']);
        $linkScore = $this->calculateLinkScore($details['internal_links']);
        $similarityScore = $this->calculateSimilarityScore($details['semantic_similarity']);

        $totalScore = $coherenceScore + $keywordScore + $depthScore + $linkScore + $similarityScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'topic_coherence' => $coherenceScore,
                    'keyword_density' => $keywordScore,
                    'topic_depth' => $depthScore,
                    'internal_links' => $linkScore,
                    'semantic_similarity' => $similarityScore,
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
        return 'Topic Authority';
    }

    private function analyzeTopicCoherence(string $content): array
    {
        $plainText = $this->stripHtml($content);
        $words = str_word_count(strtolower($plainText), 1);
        $wordCount = count($words);

        if ($wordCount === 0) {
            return ['score' => 0, 'unique_ratio' => 0, 'repeated_terms' => []];
        }

        // Count word frequencies
        $frequencies = array_count_values($words);

        // Filter out common stop words
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used',
            'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into',
            'through', 'during', 'before', 'after', 'above', 'below', 'between',
            'and', 'but', 'or', 'nor', 'so', 'yet', 'both', 'either', 'neither',
            'not', 'only', 'own', 'same', 'than', 'too', 'very', 'just', 'also',
            'this', 'that', 'these', 'those', 'it', 'its', 'they', 'their', 'them',
            'we', 'our', 'you', 'your', 'he', 'she', 'his', 'her', 'i', 'my', 'me'];

        $topicWords = array_filter($frequencies, function ($count, $word) use ($stopWords) {
            return ! in_array($word, $stopWords) && strlen($word) > 3 && $count >= 2;
        }, ARRAY_FILTER_USE_BOTH);

        arsort($topicWords);
        $topTerms = array_slice($topicWords, 0, 10, true);

        // Calculate topic coherence (repeated meaningful terms / total words)
        $repeatedCount = array_sum($topTerms);
        $coherenceRatio = $repeatedCount / $wordCount;

        return [
            'word_count' => $wordCount,
            'unique_ratio' => round(count(array_unique($words)) / $wordCount, 3),
            'coherence_ratio' => round($coherenceRatio, 3),
            'top_terms' => $topTerms,
        ];
    }

    private function analyzeKeywordDensity(string $content): array
    {
        $plainText = $this->stripHtml($content);
        $words = str_word_count(strtolower($plainText), 1);
        $wordCount = count($words);

        if ($wordCount === 0) {
            return ['density' => 0, 'distribution' => 'none'];
        }

        // Extract title/h1 for primary keyword
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $h1Match);
        $primaryKeyword = isset($h1Match[1]) ? strtolower(strip_tags($h1Match[1])) : '';

        // Count primary keyword occurrences
        $keywordCount = 0;
        $keywordWords = str_word_count($primaryKeyword, 1);

        if (! empty($keywordWords)) {
            $firstWord = $keywordWords[0] ?? '';
            if (strlen($firstWord) > 3) {
                $keywordCount = substr_count(strtolower($plainText), $firstWord);
            }
        }

        $density = $wordCount > 0 ? round(($keywordCount / $wordCount) * 100, 2) : 0;

        // Analyze distribution (check if keyword appears throughout content)
        $chunks = array_chunk($words, max(1, intval($wordCount / 4)));
        $distribution = 0;
        foreach ($chunks as $chunk) {
            if (! empty($keywordWords[0]) && in_array($keywordWords[0], $chunk)) {
                $distribution++;
            }
        }

        return [
            'primary_keyword' => $primaryKeyword,
            'occurrences' => $keywordCount,
            'density_percent' => $density,
            'distribution_score' => $distribution,
            'distribution_label' => $this->getDistributionLabel($distribution),
        ];
    }

    private function analyzeTopicDepth(string $content): array
    {
        $plainText = $this->stripHtml($content);
        $wordCount = str_word_count($plainText);
        $sentences = preg_split('/(?<=[.!?])\s+/', $plainText, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        // Calculate average sentence length
        $avgSentenceLength = $sentenceCount > 0 ? round($wordCount / $sentenceCount, 1) : 0;

        // Check for depth indicators
        $depthIndicators = [
            'examples' => preg_match_all('/\b(for example|for instance|such as|e\.g\.|i\.e\.)\b/i', $plainText),
            'explanations' => preg_match_all('/\b(because|therefore|thus|hence|consequently|as a result)\b/i', $plainText),
            'comparisons' => preg_match_all('/\b(compared to|in contrast|similarly|unlike|whereas|while)\b/i', $plainText),
            'evidence' => preg_match_all('/\b(according to|research shows|studies indicate|data suggests|evidence)\b/i', $plainText),
            'specifics' => preg_match_all('/\b(specifically|particularly|especially|notably|in particular)\b/i', $plainText),
        ];

        $totalIndicators = array_sum($depthIndicators);

        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'avg_sentence_length' => $avgSentenceLength,
            'depth_indicators' => $depthIndicators,
            'total_indicators' => $totalIndicators,
            'depth_level' => $this->getDepthLevel($wordCount, $totalIndicators),
        ];
    }

    private function analyzeInternalLinks(string $content, ?string $currentUrl): array
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER);

        $internalLinks = [];
        $externalLinks = [];
        $currentDomain = $currentUrl ? parse_url($currentUrl, PHP_URL_HOST) : null;

        foreach ($matches as $match) {
            $href = $match[1];
            $text = strip_tags($match[2]);

            // Skip anchors and javascript
            if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Determine if internal or external
            $linkDomain = parse_url($href, PHP_URL_HOST);

            if ($linkDomain === null || $linkDomain === $currentDomain) {
                $internalLinks[] = ['href' => $href, 'text' => $this->truncate($text, 50)];
            } else {
                $externalLinks[] = ['href' => $href, 'text' => $this->truncate($text, 50)];
            }
        }

        return [
            'internal_count' => count($internalLinks),
            'external_count' => count($externalLinks),
            'internal_links' => array_slice($internalLinks, 0, 10),
            'external_links' => array_slice($externalLinks, 0, 5),
            'ratio' => count($externalLinks) > 0
                ? round(count($internalLinks) / count($externalLinks), 2)
                : count($internalLinks),
        ];
    }

    private function calculateSemanticSimilarity(array $embedding, int $teamId, ?int $excludeId = null): array
    {
        try {
            $embeddingStr = '['.implode(',', $embedding).']';

            $query = DB::table('documents')
                ->select('id', 'title')
                ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$embeddingStr])
                ->where('team_id', $teamId)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->orderByDesc('similarity')
                ->limit(5);

            $similar = $query->get();

            $avgSimilarity = $similar->avg('similarity') ?? 0;

            return [
                'average_similarity' => round($avgSimilarity, 4),
                'similar_documents' => $similar->map(fn ($doc) => [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'similarity' => round($doc->similarity, 4),
                ])->toArray(),
                'topic_focus' => $this->getTopicFocusLabel($avgSimilarity),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to calculate semantic similarity',
                'average_similarity' => 0,
            ];
        }
    }

    private function calculateCoherenceScore(array $coherence): float
    {
        // Up to 6 points
        $ratio = $coherence['coherence_ratio'] ?? 0;

        if ($ratio >= 0.15) {
            return 6;
        }
        if ($ratio >= 0.10) {
            return 4;
        }
        if ($ratio >= 0.05) {
            return 2;
        }

        return 0;
    }

    private function calculateKeywordScore(array $keyword): float
    {
        // Up to 5 points
        $score = 0;

        // Density between 1-3% is optimal
        $density = $keyword['density_percent'] ?? 0;
        if ($density >= 1 && $density <= 3) {
            $score += 3;
        } elseif ($density > 0 && $density < 5) {
            $score += 1.5;
        }

        // Good distribution
        if (($keyword['distribution_score'] ?? 0) >= 3) {
            $score += 2;
        } elseif (($keyword['distribution_score'] ?? 0) >= 2) {
            $score += 1;
        }

        return min(5, $score);
    }

    private function calculateDepthScore(array $depth): float
    {
        // Up to 6 points
        $score = 0;

        // Word count (comprehensive content)
        $wordCount = $depth['word_count'] ?? 0;
        if ($wordCount >= 1500) {
            $score += 2;
        } elseif ($wordCount >= 800) {
            $score += 1;
        }

        // Depth indicators
        $indicators = $depth['total_indicators'] ?? 0;
        if ($indicators >= 10) {
            $score += 4;
        } elseif ($indicators >= 5) {
            $score += 2;
        } elseif ($indicators >= 2) {
            $score += 1;
        }

        return min(6, $score);
    }

    private function calculateLinkScore(array $links): float
    {
        // Up to 4 points
        $score = 0;
        $internalCount = $links['internal_count'] ?? 0;

        if ($internalCount >= 5) {
            $score += 4;
        } elseif ($internalCount >= 3) {
            $score += 2.5;
        } elseif ($internalCount >= 1) {
            $score += 1;
        }

        return min(4, $score);
    }

    private function calculateSimilarityScore(?array $similarity): float
    {
        // Up to 4 points
        if ($similarity === null || isset($similarity['error'])) {
            return 0;
        }

        $avg = $similarity['average_similarity'] ?? 0;

        if ($avg >= 0.8) {
            return 4;
        }
        if ($avg >= 0.6) {
            return 3;
        }
        if ($avg >= 0.4) {
            return 2;
        }
        if ($avg >= 0.2) {
            return 1;
        }

        return 0;
    }

    private function getDistributionLabel(int $score): string
    {
        return match (true) {
            $score >= 4 => 'excellent',
            $score >= 3 => 'good',
            $score >= 2 => 'moderate',
            $score >= 1 => 'poor',
            default => 'none',
        };
    }

    private function getDepthLevel(int $wordCount, int $indicators): string
    {
        $score = ($wordCount / 500) + ($indicators / 2);

        return match (true) {
            $score >= 8 => 'comprehensive',
            $score >= 5 => 'detailed',
            $score >= 3 => 'moderate',
            $score >= 1 => 'basic',
            default => 'minimal',
        };
    }

    private function getTopicFocusLabel(float $similarity): string
    {
        return match (true) {
            $similarity >= 0.8 => 'highly focused',
            $similarity >= 0.6 => 'focused',
            $similarity >= 0.4 => 'moderately focused',
            $similarity >= 0.2 => 'loosely related',
            default => 'diverse topics',
        };
    }

    private function stripHtml(string $content): string
    {
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

        return trim(strip_tags($content));
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
