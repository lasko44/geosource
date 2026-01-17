<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on high-confidence answerability.
 *
 * Measures:
 * - Declarative language (confident statements)
 * - Low uncertainty (few hedging words)
 * - LLM-ready phrasing (direct answers)
 * - Quotable snippets
 */
class AnswerabilityScorer implements ScorerInterface
{
    private const MAX_SCORE = 20;

    private const DECLARATIVE_PATTERNS = [
        '/\bis\b/i',
        '/\bare\b/i',
        '/\bwill\b/i',
        '/\bcan\b/i',
        '/\bdoes\b/i',
        '/\bprovides?\b/i',
        '/\boffers?\b/i',
        '/\bincludes?\b/i',
        '/\brequires?\b/i',
        '/\bconsists?\b/i',
    ];

    private const HEDGING_WORDS = [
        'maybe', 'perhaps', 'possibly', 'probably', 'might', 'could be',
        'it seems', 'appears to', 'sort of', 'kind of', 'somewhat',
        'in some cases', 'sometimes', 'often', 'usually', 'generally',
        'tend to', 'likely', 'unlikely', 'uncertain', 'unclear',
        'it depends', 'varies', 'may or may not', 'not always',
    ];

    private const CONFIDENCE_INDICATORS = [
        'is defined as', 'means', 'refers to', 'consists of',
        'the answer is', 'the solution is', 'the key is',
        'specifically', 'exactly', 'precisely', 'always', 'never',
        'must', 'should', 'the best', 'the most', 'the only',
        'according to', 'research shows', 'studies confirm',
        'in conclusion', 'therefore', 'thus', 'as a result',
    ];

    public function score(string $content, array $context = []): array
    {
        $plainText = $this->stripHtml($content);
        $sentences = $this->getSentences($plainText);

        $details = [
            'declarative' => $this->analyzeDeclarative($sentences),
            'uncertainty' => $this->analyzeUncertainty($plainText, $sentences),
            'confidence' => $this->analyzeConfidence($plainText, $sentences),
            'snippets' => $this->analyzeSnippets($sentences),
            'directness' => $this->analyzeDirectness($content, $sentences),
        ];

        // Calculate scores
        $declarativeScore = $this->calculateDeclarativeScore($details['declarative']);
        $uncertaintyScore = $this->calculateUncertaintyScore($details['uncertainty']);
        $confidenceScore = $this->calculateConfidenceScore($details['confidence']);
        $snippetScore = $this->calculateSnippetScore($details['snippets']);
        $directnessScore = $this->calculateDirectnessScore($details['directness']);

        $totalScore = $declarativeScore + $uncertaintyScore + $confidenceScore + $snippetScore + $directnessScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'declarative_language' => $declarativeScore,
                    'low_uncertainty' => $uncertaintyScore,
                    'confidence_indicators' => $confidenceScore,
                    'quotable_snippets' => $snippetScore,
                    'directness' => $directnessScore,
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
        return 'High-Confidence Answerability';
    }

    private function analyzeDeclarative(array $sentences): array
    {
        $declarativeSentences = 0;
        $questionSentences = 0;

        foreach ($sentences as $sentence) {
            // Skip very short sentences
            if (strlen($sentence) < 20) {
                continue;
            }

            // Count questions
            if (str_ends_with(trim($sentence), '?')) {
                $questionSentences++;

                continue;
            }

            // Check for declarative patterns
            foreach (self::DECLARATIVE_PATTERNS as $pattern) {
                if (preg_match($pattern, $sentence)) {
                    $declarativeSentences++;
                    break;
                }
            }
        }

        $totalSentences = count($sentences);
        $declarativeRatio = $totalSentences > 0 ? $declarativeSentences / $totalSentences : 0;

        return [
            'total_sentences' => $totalSentences,
            'declarative_count' => $declarativeSentences,
            'question_count' => $questionSentences,
            'declarative_ratio' => round($declarativeRatio, 3),
        ];
    }

    private function analyzeUncertainty(string $text, array $sentences): array
    {
        $hedgingCount = 0;
        $hedgingExamples = [];
        $textLower = strtolower($text);

        foreach (self::HEDGING_WORDS as $hedge) {
            $count = substr_count($textLower, $hedge);
            if ($count > 0) {
                $hedgingCount += $count;
                $hedgingExamples[] = ['word' => $hedge, 'count' => $count];
            }
        }

        // Sort by count and limit
        usort($hedgingExamples, fn ($a, $b) => $b['count'] - $a['count']);
        $hedgingExamples = array_slice($hedgingExamples, 0, 5);

        $wordCount = str_word_count($text);
        $hedgingDensity = $wordCount > 0 ? ($hedgingCount / $wordCount) * 100 : 0;

        return [
            'hedging_count' => $hedgingCount,
            'hedging_density' => round($hedgingDensity, 3),
            'examples' => $hedgingExamples,
            'uncertainty_level' => $this->getUncertaintyLevel($hedgingDensity),
        ];
    }

    private function analyzeConfidence(string $text, array $sentences): array
    {
        $confidenceCount = 0;
        $confidenceExamples = [];
        $textLower = strtolower($text);

        foreach (self::CONFIDENCE_INDICATORS as $indicator) {
            $count = substr_count($textLower, $indicator);
            if ($count > 0) {
                $confidenceCount += $count;
                $confidenceExamples[] = ['phrase' => $indicator, 'count' => $count];
            }
        }

        // Sort by count and limit
        usort($confidenceExamples, fn ($a, $b) => $b['count'] - $a['count']);
        $confidenceExamples = array_slice($confidenceExamples, 0, 5);

        $wordCount = str_word_count($text);
        $confidenceDensity = $wordCount > 0 ? ($confidenceCount / $wordCount) * 100 : 0;

        return [
            'confidence_count' => $confidenceCount,
            'confidence_density' => round($confidenceDensity, 3),
            'examples' => $confidenceExamples,
            'confidence_level' => $this->getConfidenceLevel($confidenceCount),
        ];
    }

    private function analyzeSnippets(array $sentences): array
    {
        $quotableSnippets = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            $length = strlen($sentence);

            // Ideal snippet: 50-200 characters, declarative, no questions
            if ($length < 40 || $length > 250) {
                continue;
            }

            if (str_ends_with($sentence, '?')) {
                continue;
            }

            // Check if it's a self-contained statement
            $hasDefinition = preg_match('/\b(is|are|means|refers to|defined as)\b/i', $sentence);
            $startsWithCapital = preg_match('/^[A-Z]/', $sentence);
            $endsWithPeriod = str_ends_with($sentence, '.');

            if ($hasDefinition && $startsWithCapital && $endsWithPeriod) {
                $quotableSnippets[] = [
                    'text' => $this->truncate($sentence, 200),
                    'length' => $length,
                    'type' => 'definition',
                ];
            }
        }

        // Limit and categorize
        $quotableSnippets = array_slice($quotableSnippets, 0, 5);

        return [
            'count' => count($quotableSnippets),
            'snippets' => $quotableSnippets,
            'has_featured_snippet_candidates' => count($quotableSnippets) > 0,
        ];
    }

    private function analyzeDirectness(string $content, array $sentences): array
    {
        // Check for direct answer patterns
        $directPatterns = [
            'numbered_steps' => preg_match_all('/(?:^|\n)\s*\d+[\.\)]\s+/m', strip_tags($content)),
            'bullet_points' => preg_match_all('/<li[^>]*>/i', $content),
            'bold_emphasis' => preg_match_all('/<(strong|b)[^>]*>/i', $content),
            'tables' => preg_match_all('/<table[^>]*>/i', $content),
        ];

        // Check for first-sentence answers (content starts with answer, not preamble)
        $firstSentence = $sentences[0] ?? '';
        $startsWithAnswer = false;

        if (! empty($firstSentence)) {
            // Good: Starts with subject matter, not meta-commentary
            $metaPhrases = ['in this article', 'in this post', 'today we will', 'let\'s explore', 'welcome to'];
            $hasMetaPreamble = false;
            foreach ($metaPhrases as $phrase) {
                if (stripos($firstSentence, $phrase) !== false) {
                    $hasMetaPreamble = true;
                    break;
                }
            }
            $startsWithAnswer = ! $hasMetaPreamble;
        }

        // Calculate total direct formatting elements
        $totalDirectElements = array_sum($directPatterns);

        return [
            'patterns' => $directPatterns,
            'total_direct_elements' => $totalDirectElements,
            'starts_with_answer' => $startsWithAnswer,
            'first_sentence_preview' => $this->truncate($firstSentence, 100),
            'directness_level' => $this->getDirectnessLevel($totalDirectElements, $startsWithAnswer),
        ];
    }

    private function calculateDeclarativeScore(array $declarative): float
    {
        // Up to 5 points
        $ratio = $declarative['declarative_ratio'];

        if ($ratio >= 0.7) {
            return 5;
        }
        if ($ratio >= 0.5) {
            return 4;
        }
        if ($ratio >= 0.3) {
            return 2.5;
        }
        if ($ratio >= 0.1) {
            return 1;
        }

        return 0;
    }

    private function calculateUncertaintyScore(array $uncertainty): float
    {
        // Up to 4 points (higher score = less uncertainty)
        $density = $uncertainty['hedging_density'];

        if ($density <= 0.1) {
            return 4;
        }
        if ($density <= 0.3) {
            return 3;
        }
        if ($density <= 0.5) {
            return 2;
        }
        if ($density <= 1.0) {
            return 1;
        }

        return 0;
    }

    private function calculateConfidenceScore(array $confidence): float
    {
        // Up to 4 points
        $count = $confidence['confidence_count'];

        if ($count >= 10) {
            return 4;
        }
        if ($count >= 5) {
            return 3;
        }
        if ($count >= 3) {
            return 2;
        }
        if ($count >= 1) {
            return 1;
        }

        return 0;
    }

    private function calculateSnippetScore(array $snippets): float
    {
        // Up to 4 points
        $count = $snippets['count'];

        if ($count >= 3) {
            return 4;
        }
        if ($count >= 2) {
            return 3;
        }
        if ($count >= 1) {
            return 2;
        }

        return 0;
    }

    private function calculateDirectnessScore(array $directness): float
    {
        // Up to 3 points
        $score = 0;

        if ($directness['starts_with_answer']) {
            $score += 1.5;
        }

        if ($directness['total_direct_elements'] >= 5) {
            $score += 1.5;
        } elseif ($directness['total_direct_elements'] >= 2) {
            $score += 0.75;
        }

        return min(3, $score);
    }

    private function getUncertaintyLevel(float $density): string
    {
        return match (true) {
            $density <= 0.1 => 'very low',
            $density <= 0.3 => 'low',
            $density <= 0.5 => 'moderate',
            $density <= 1.0 => 'high',
            default => 'very high',
        };
    }

    private function getConfidenceLevel(int $count): string
    {
        return match (true) {
            $count >= 10 => 'very high',
            $count >= 5 => 'high',
            $count >= 3 => 'moderate',
            $count >= 1 => 'low',
            default => 'very low',
        };
    }

    private function getDirectnessLevel(int $elements, bool $startsWithAnswer): string
    {
        $score = $elements + ($startsWithAnswer ? 5 : 0);

        return match (true) {
            $score >= 10 => 'very direct',
            $score >= 6 => 'direct',
            $score >= 3 => 'moderate',
            $score >= 1 => 'indirect',
            default => 'very indirect',
        };
    }

    private function stripHtml(string $content): string
    {
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

        return trim(strip_tags($content));
    }

    private function getSentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, fn ($s) => strlen(trim($s)) > 10);
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
