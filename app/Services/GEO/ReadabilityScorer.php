<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on readability and accessibility.
 *
 * AGENCY TIER FEATURE
 *
 * Measures:
 * - Flesch-Kincaid readability
 * - Sentence complexity
 * - Paragraph structure
 * - Language clarity
 */
class ReadabilityScorer implements ScorerInterface
{
    private const MAX_SCORE = 10;

    public function score(string $content, array $context = []): array
    {
        // Extract clean text
        $text = $this->extractText($content);

        $details = [
            'flesch_kincaid' => $this->calculateFleschKincaid($text),
            'sentence_analysis' => $this->analyzeSentences($text),
            'paragraph_analysis' => $this->analyzeParagraphs($content),
            'word_analysis' => $this->analyzeWords($text),
        ];

        // Calculate scores (total: 10 points)
        $fkScore = $this->calculateFKScore($details['flesch_kincaid']);           // Up to 4 pts
        $sentenceScore = $this->calculateSentenceScore($details['sentence_analysis']); // Up to 3 pts
        $paragraphScore = $this->calculateParagraphScore($details['paragraph_analysis']); // Up to 2 pts
        $wordScore = $this->calculateWordScore($details['word_analysis']);        // Up to 1 pt

        $totalScore = $fkScore + $sentenceScore + $paragraphScore + $wordScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'flesch_kincaid' => $fkScore,
                    'sentence_structure' => $sentenceScore,
                    'paragraph_structure' => $paragraphScore,
                    'word_complexity' => $wordScore,
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
        return 'Readability';
    }

    private function extractText(string $content): string
    {
        // Remove scripts and styles
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $text);

        // Strip tags and decode entities
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function calculateFleschKincaid(string $text): array
    {
        $sentences = $this->countSentences($text);
        $words = $this->countWords($text);
        $syllables = $this->countSyllables($text);

        if ($sentences === 0 || $words === 0) {
            return [
                'reading_ease' => 0,
                'grade_level' => 0,
                'reading_level' => 'unknown',
                'sentences' => $sentences,
                'words' => $words,
                'syllables' => $syllables,
            ];
        }

        // Flesch Reading Ease: 206.835 - 1.015(words/sentences) - 84.6(syllables/words)
        $readingEase = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));
        $readingEase = max(0, min(100, $readingEase));

        // Flesch-Kincaid Grade Level: 0.39(words/sentences) + 11.8(syllables/words) - 15.59
        $gradeLevel = (0.39 * ($words / $sentences)) + (11.8 * ($syllables / $words)) - 15.59;
        $gradeLevel = max(0, $gradeLevel);

        return [
            'reading_ease' => round($readingEase, 1),
            'grade_level' => round($gradeLevel, 1),
            'reading_level' => $this->getReadingLevel($readingEase),
            'sentences' => $sentences,
            'words' => $words,
            'syllables' => $syllables,
            'avg_words_per_sentence' => round($words / $sentences, 1),
            'avg_syllables_per_word' => round($syllables / $words, 2),
        ];
    }

    private function countSentences(string $text): int
    {
        // Count sentence-ending punctuation
        preg_match_all('/[.!?]+/', $text, $matches);

        return max(1, count($matches[0]));
    }

    private function countWords(string $text): int
    {
        return str_word_count($text);
    }

    private function countSyllables(string $text): int
    {
        $words = preg_split('/\s+/', strtolower($text));
        $totalSyllables = 0;

        foreach ($words as $word) {
            $totalSyllables += $this->countWordSyllables($word);
        }

        return $totalSyllables;
    }

    private function countWordSyllables(string $word): int
    {
        $word = preg_replace('/[^a-z]/', '', strtolower($word));

        if (strlen($word) <= 3) {
            return 1;
        }

        // Remove silent e at end
        $word = preg_replace('/e$/', '', $word);

        // Count vowel groups
        preg_match_all('/[aeiouy]+/', $word, $matches);
        $count = count($matches[0]);

        return max(1, $count);
    }

    private function getReadingLevel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'very_easy',     // 5th grade
            $score >= 80 => 'easy',          // 6th grade
            $score >= 70 => 'fairly_easy',   // 7th grade
            $score >= 60 => 'standard',      // 8th-9th grade
            $score >= 50 => 'fairly_hard',   // 10th-12th grade
            $score >= 30 => 'hard',          // College
            default => 'very_hard',           // College graduate
        };
    }

    private function analyzeSentences(string $text): array
    {
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return [
                'total' => 0,
                'avg_length' => 0,
                'short_sentences' => 0,
                'medium_sentences' => 0,
                'long_sentences' => 0,
                'very_long_sentences' => 0,
                'variety_score' => 0,
            ];
        }

        $lengths = [];
        $short = $medium = $long = $veryLong = 0;

        foreach ($sentences as $sentence) {
            $wordCount = str_word_count($sentence);
            $lengths[] = $wordCount;

            if ($wordCount <= 10) {
                $short++;
            } elseif ($wordCount <= 20) {
                $medium++;
            } elseif ($wordCount <= 35) {
                $long++;
            } else {
                $veryLong++;
            }
        }

        $avgLength = array_sum($lengths) / $sentenceCount;

        // Calculate variety score (good content has mix of sentence lengths)
        $varietyScore = 0;
        if ($short > 0 && $medium > 0) {
            $varietyScore = min(100, (($short + $medium) / $sentenceCount) * 100);
        }

        return [
            'total' => $sentenceCount,
            'avg_length' => round($avgLength, 1),
            'short_sentences' => $short,
            'medium_sentences' => $medium,
            'long_sentences' => $long,
            'very_long_sentences' => $veryLong,
            'variety_score' => round($varietyScore, 1),
        ];
    }

    private function analyzeParagraphs(string $content): array
    {
        // Count paragraphs
        preg_match_all('/<p[^>]*>.*?<\/p>/is', $content, $paragraphs);
        $paragraphCount = count($paragraphs[0]);

        if ($paragraphCount === 0) {
            return [
                'total' => 0,
                'avg_length' => 0,
                'short_paragraphs' => 0,
                'medium_paragraphs' => 0,
                'long_paragraphs' => 0,
                'optimal_ratio' => 0,
            ];
        }

        $lengths = [];
        $short = $medium = $long = 0;

        foreach ($paragraphs[0] as $p) {
            $text = strip_tags($p);
            $wordCount = str_word_count($text);
            $lengths[] = $wordCount;

            if ($wordCount <= 50) {
                $short++;
            } elseif ($wordCount <= 150) {
                $medium++;
            } else {
                $long++;
            }
        }

        $avgLength = array_sum($lengths) / $paragraphCount;

        // Optimal ratio: most paragraphs should be short to medium
        $optimalRatio = (($short + $medium) / $paragraphCount) * 100;

        return [
            'total' => $paragraphCount,
            'avg_length' => round($avgLength, 1),
            'short_paragraphs' => $short,
            'medium_paragraphs' => $medium,
            'long_paragraphs' => $long,
            'optimal_ratio' => round($optimalRatio, 1),
        ];
    }

    private function analyzeWords(string $text): array
    {
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, fn ($w) => strlen($w) > 0);
        $wordCount = count($words);

        if ($wordCount === 0) {
            return [
                'total' => 0,
                'avg_length' => 0,
                'complex_words' => 0,
                'complex_ratio' => 0,
                'common_words_ratio' => 0,
            ];
        }

        $lengths = [];
        $complexWords = 0;

        // Common simple words
        $commonWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i',
            'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at',
            'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she',
            'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what',
            'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me',
            'is', 'are', 'was', 'were', 'been', 'being', 'has', 'had', 'can', 'could'];

        $commonWordsFound = 0;

        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if (empty($word)) {
                continue;
            }

            $lengths[] = strlen($word);

            // Complex word: 3+ syllables
            if ($this->countWordSyllables($word) >= 3) {
                $complexWords++;
            }

            if (in_array($word, $commonWords)) {
                $commonWordsFound++;
            }
        }

        $avgLength = count($lengths) > 0 ? array_sum($lengths) / count($lengths) : 0;

        return [
            'total' => $wordCount,
            'avg_length' => round($avgLength, 1),
            'complex_words' => $complexWords,
            'complex_ratio' => round(($complexWords / $wordCount) * 100, 1),
            'common_words_ratio' => round(($commonWordsFound / $wordCount) * 100, 1),
        ];
    }

    private function calculateFKScore(array $fk): float
    {
        $score = 0;

        // Reading ease scoring (60-70 is standard, good for most content)
        $readingEase = $fk['reading_ease'];

        if ($readingEase >= 60 && $readingEase <= 80) {
            $score += 4; // Optimal range
        } elseif ($readingEase >= 50 && $readingEase < 60) {
            $score += 3; // Acceptable
        } elseif ($readingEase >= 80) {
            $score += 3; // Very easy (might be too simple)
        } elseif ($readingEase >= 40) {
            $score += 2; // Getting difficult
        } elseif ($readingEase >= 30) {
            $score += 1; // Hard
        }
        // Below 30 = 0 points

        return min(4, $score);
    }

    private function calculateSentenceScore(array $sentences): float
    {
        $score = 0;

        // Average sentence length (15-20 words is ideal)
        $avgLength = $sentences['avg_length'];
        if ($avgLength >= 12 && $avgLength <= 22) {
            $score += 1.5;
        } elseif ($avgLength >= 8 && $avgLength <= 28) {
            $score += 1;
        } else {
            $score += 0.5;
        }

        // Sentence variety
        if ($sentences['variety_score'] >= 60) {
            $score += 1;
        } elseif ($sentences['variety_score'] >= 40) {
            $score += 0.5;
        }

        // Penalty for too many very long sentences
        if ($sentences['total'] > 0) {
            $veryLongRatio = $sentences['very_long_sentences'] / $sentences['total'];
            if ($veryLongRatio < 0.1) {
                $score += 0.5;
            }
        }

        return min(3, $score);
    }

    private function calculateParagraphScore(array $paragraphs): float
    {
        $score = 0;

        // Optimal paragraph ratio
        if ($paragraphs['optimal_ratio'] >= 80) {
            $score += 1.5;
        } elseif ($paragraphs['optimal_ratio'] >= 60) {
            $score += 1;
        } elseif ($paragraphs['optimal_ratio'] >= 40) {
            $score += 0.5;
        }

        // Average paragraph length (50-100 words is ideal for web)
        $avgLength = $paragraphs['avg_length'];
        if ($avgLength >= 40 && $avgLength <= 120) {
            $score += 0.5;
        }

        return min(2, $score);
    }

    private function calculateWordScore(array $words): float
    {
        $score = 0;

        // Complex word ratio (lower is better for readability, but some complexity shows expertise)
        $complexRatio = $words['complex_ratio'];
        if ($complexRatio >= 10 && $complexRatio <= 25) {
            $score += 1; // Good balance
        } elseif ($complexRatio < 10) {
            $score += 0.75; // Very simple
        } elseif ($complexRatio <= 35) {
            $score += 0.5; // Somewhat complex
        }
        // Over 35% = 0 points

        return min(1, $score);
    }
}
