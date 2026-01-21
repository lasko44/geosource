<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on question-answer coverage.
 *
 * AGENCY TIER FEATURE
 *
 * Measures:
 * - Question patterns in content
 * - Answer completeness
 * - FAQ-style coverage
 * - "People Also Ask" style anticipation
 */
class QuestionCoverageScorer implements ScorerInterface
{
    private const MAX_SCORE = 10;

    public function score(string $content, array $context = []): array
    {
        $text = strip_tags($content);

        $details = [
            'questions' => $this->analyzeQuestions($content, $text),
            'answers' => $this->analyzeAnswers($content, $text),
            'qa_patterns' => $this->analyzeQAPatterns($content),
            'anticipation' => $this->analyzeQuestionAnticipation($text),
        ];

        // Calculate scores (total: 10 points)
        $questionScore = $this->calculateQuestionScore($details['questions']);    // Up to 3 pts
        $answerScore = $this->calculateAnswerScore($details['answers']);          // Up to 3 pts
        $patternScore = $this->calculatePatternScore($details['qa_patterns']);    // Up to 2 pts
        $anticipationScore = $this->calculateAnticipationScore($details['anticipation']); // Up to 2 pts

        $totalScore = $questionScore + $answerScore + $patternScore + $anticipationScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'questions' => $questionScore,
                    'answers' => $answerScore,
                    'qa_patterns' => $patternScore,
                    'anticipation' => $anticipationScore,
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
        return 'Question Coverage';
    }

    private function analyzeQuestions(string $content, string $text): array
    {
        $result = [
            'heading_questions' => [],
            'inline_questions' => [],
            'total_questions' => 0,
            'question_types' => [],
        ];

        // Find questions in headings
        preg_match_all('/<h[2-6][^>]*>([^<]*\?)<\/h[2-6]>/i', $content, $headingMatches);
        $result['heading_questions'] = array_map('trim', $headingMatches[1]);

        // Find inline questions
        preg_match_all('/(?:^|[.!?]\s+)([A-Z][^.!?]*\?)(?:\s|$)/m', $text, $inlineMatches);
        $result['inline_questions'] = array_slice(array_unique($inlineMatches[1]), 0, 10);

        $result['total_questions'] = count($result['heading_questions']) + count($result['inline_questions']);

        // Categorize question types
        $allQuestions = array_merge($result['heading_questions'], $result['inline_questions']);
        foreach ($allQuestions as $q) {
            $type = $this->categorizeQuestion($q);
            if (! isset($result['question_types'][$type])) {
                $result['question_types'][$type] = 0;
            }
            $result['question_types'][$type]++;
        }

        return $result;
    }

    private function categorizeQuestion(string $question): string
    {
        $q = strtolower($question);

        return match (true) {
            str_starts_with($q, 'what') => 'what',
            str_starts_with($q, 'how') => 'how',
            str_starts_with($q, 'why') => 'why',
            str_starts_with($q, 'when') => 'when',
            str_starts_with($q, 'where') => 'where',
            str_starts_with($q, 'who') => 'who',
            str_starts_with($q, 'which') => 'which',
            str_starts_with($q, 'is') || str_starts_with($q, 'are') ||
            str_starts_with($q, 'do') || str_starts_with($q, 'does') ||
            str_starts_with($q, 'can') || str_starts_with($q, 'should') => 'yes_no',
            default => 'other',
        };
    }

    private function analyzeAnswers(string $content, string $text): array
    {
        $result = [
            'direct_answers' => 0,
            'definition_answers' => 0,
            'list_answers' => 0,
            'has_immediate_answers' => false,
            'answer_patterns' => [],
        ];

        // Direct answer patterns (sentences that start with answer-like phrases)
        $directPatterns = [
            '/(?:^|\n)\s*(?:The answer is|Yes,|No,|It is|They are|This is)/mi',
            '/(?:^|\n)\s*[A-Z][^.!?]{10,100}(?:is|are|means|refers to)[^.!?]{10,100}\./m',
        ];

        foreach ($directPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $result['direct_answers'] += count($matches[0]);
                $result['answer_patterns'][] = 'direct_statement';
            }
        }

        // Definition-style answers
        $defPatterns = [
            '/(?:is defined as|refers to|is a|means that|is when)/i',
        ];

        foreach ($defPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $result['definition_answers'] += count($matches[0]);
                $result['answer_patterns'][] = 'definition';
            }
        }

        // List-based answers (following question headings)
        preg_match_all('/<h[2-6][^>]*>[^<]*\?<\/h[2-6]>\s*(?:<[^>]+>\s*)*<(?:ul|ol)[^>]*>/is', $content, $listMatches);
        $result['list_answers'] = count($listMatches[0]);
        if ($result['list_answers'] > 0) {
            $result['answer_patterns'][] = 'list';
        }

        // Check for immediate answers after questions
        preg_match_all('/<h[2-6][^>]*>[^<]*\?<\/h[2-6]>\s*<p[^>]*>[^<]{20,}/is', $content, $immediateMatches);
        $result['has_immediate_answers'] = count($immediateMatches[0]) > 0;

        $result['answer_patterns'] = array_unique($result['answer_patterns']);
        $result['total_answers'] = $result['direct_answers'] + $result['definition_answers'] + $result['list_answers'];

        return $result;
    }

    private function analyzeQAPatterns(string $content): array
    {
        $result = [
            'has_faq_section' => false,
            'has_qa_schema' => false,
            'has_accordion' => false,
            'has_question_headings' => false,
            'qa_pairs_count' => 0,
        ];

        // FAQ section detection
        if (preg_match('/<h[2-4][^>]*>[^<]*(?:FAQ|Frequently Asked|Common Questions)[^<]*<\/h[2-4]>/i', $content)) {
            $result['has_faq_section'] = true;
        }

        // QA/FAQ schema
        if (preg_match('/"@type"\s*:\s*"(?:FAQPage|QAPage)"/i', $content)) {
            $result['has_qa_schema'] = true;
        }

        // Accordion patterns (common for Q&A)
        if (preg_match('/class=["\'][^"\']*(?:accordion|collapsible|expand)/i', $content) ||
            preg_match('/<details[^>]*>.*?<summary[^>]*>/is', $content)) {
            $result['has_accordion'] = true;
        }

        // Question headings
        preg_match_all('/<h[2-6][^>]*>[^<]*\?<\/h[2-6]>/i', $content, $questionHeadings);
        if (count($questionHeadings[0]) >= 2) {
            $result['has_question_headings'] = true;
            $result['qa_pairs_count'] = count($questionHeadings[0]);
        }

        return $result;
    }

    private function analyzeQuestionAnticipation(string $text): array
    {
        $result = [
            'covers_what' => false,
            'covers_how' => false,
            'covers_why' => false,
            'covers_when_where' => false,
            'coverage_score' => 0,
            'anticipation_phrases' => [],
        ];

        // Check for different question type coverage
        $whatPatterns = ['/what\s+(?:is|are|does)/i', '/\bis\s+(?:a|an|the)\b/i'];
        $howPatterns = ['/how\s+(?:to|do|does|can)/i', '/steps?\s+(?:to|for)/i', '/guide\s+to/i'];
        $whyPatterns = ['/why\s+(?:is|are|do|does|should)/i', '/because\b/i', '/reason(?:s)?\s+(?:for|why)/i'];
        $whenWherePatterns = ['/when\s+(?:to|should|do)/i', '/where\s+(?:to|can|do)/i', '/best\s+time/i'];

        foreach ($whatPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $result['covers_what'] = true;
                break;
            }
        }

        foreach ($howPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $result['covers_how'] = true;
                break;
            }
        }

        foreach ($whyPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $result['covers_why'] = true;
                break;
            }
        }

        foreach ($whenWherePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $result['covers_when_where'] = true;
                break;
            }
        }

        // Anticipation phrases
        $anticipationPatterns = [
            '/you (?:may|might) (?:be )?wonder/i' => 'addresses wondering',
            '/(?:common|frequently)\s+(?:asked\s+)?question/i' => 'acknowledges common questions',
            '/(?:many|some)\s+(?:people|users)\s+ask/i' => 'references user questions',
            '/(?:let\'?s|we\'?ll)\s+(?:explore|explain|answer)/i' => 'promises explanation',
            '/(?:in this|here\'?s|below)/i' => 'provides navigation',
        ];

        foreach ($anticipationPatterns as $pattern => $description) {
            if (preg_match($pattern, $text)) {
                $result['anticipation_phrases'][] = $description;
            }
        }

        // Calculate coverage score
        $coverage = array_sum([
            $result['covers_what'] ? 25 : 0,
            $result['covers_how'] ? 25 : 0,
            $result['covers_why'] ? 25 : 0,
            $result['covers_when_where'] ? 25 : 0,
        ]);
        $result['coverage_score'] = $coverage;

        return $result;
    }

    private function calculateQuestionScore(array $questions): float
    {
        $score = 0;

        // Questions in headings (most valuable)
        $headingCount = count($questions['heading_questions']);
        if ($headingCount >= 5) {
            $score += 2;
        } elseif ($headingCount >= 3) {
            $score += 1.5;
        } elseif ($headingCount >= 1) {
            $score += 1;
        }

        // Variety of question types
        $typeCount = count($questions['question_types']);
        if ($typeCount >= 4) {
            $score += 1;
        } elseif ($typeCount >= 2) {
            $score += 0.5;
        }

        return min(3, $score);
    }

    private function calculateAnswerScore(array $answers): float
    {
        $score = 0;

        // Has answers
        if ($answers['total_answers'] >= 5) {
            $score += 1.5;
        } elseif ($answers['total_answers'] >= 2) {
            $score += 1;
        } elseif ($answers['total_answers'] >= 1) {
            $score += 0.5;
        }

        // Answer variety
        if (count($answers['answer_patterns']) >= 2) {
            $score += 1;
        } elseif (count($answers['answer_patterns']) >= 1) {
            $score += 0.5;
        }

        // Immediate answers
        if ($answers['has_immediate_answers']) {
            $score += 0.5;
        }

        return min(3, $score);
    }

    private function calculatePatternScore(array $patterns): float
    {
        $score = 0;

        if ($patterns['has_faq_section']) {
            $score += 0.75;
        }

        if ($patterns['has_qa_schema']) {
            $score += 0.5;
        }

        if ($patterns['has_accordion']) {
            $score += 0.25;
        }

        if ($patterns['has_question_headings']) {
            $score += 0.5;
        }

        return min(2, $score);
    }

    private function calculateAnticipationScore(array $anticipation): float
    {
        $score = 0;

        // Coverage of question types
        if ($anticipation['coverage_score'] >= 75) {
            $score += 1.5;
        } elseif ($anticipation['coverage_score'] >= 50) {
            $score += 1;
        } elseif ($anticipation['coverage_score'] >= 25) {
            $score += 0.5;
        }

        // Anticipation phrases
        if (count($anticipation['anticipation_phrases']) >= 2) {
            $score += 0.5;
        }

        return min(2, $score);
    }
}
