<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * GEO (Generative Engine Optimization) Scorer
 *
 * Orchestrates all scoring pillars to produce a comprehensive
 * GEO score (0-100) with explainable, actionable insights.
 *
 * Pillars:
 * - Clear Definitions (20 points)
 * - Structured Knowledge (20 points)
 * - Topic Authority (25 points)
 * - Machine-Readable Formatting (15 points)
 * - High-Confidence Answerability (20 points)
 */
class GeoScorer
{
    private array $scorers = [];

    public function __construct(
        ?DefinitionScorer $definitionScorer = null,
        ?StructureScorer $structureScorer = null,
        ?AuthorityScorer $authorityScorer = null,
        ?MachineReadableScorer $machineReadableScorer = null,
        ?AnswerabilityScorer $answerabilityScorer = null,
    ) {
        $this->scorers = [
            'definitions' => $definitionScorer ?? new DefinitionScorer,
            'structure' => $structureScorer ?? new StructureScorer,
            'authority' => $authorityScorer ?? new AuthorityScorer,
            'machine_readable' => $machineReadableScorer ?? new MachineReadableScorer,
            'answerability' => $answerabilityScorer ?? new AnswerabilityScorer,
        ];
    }

    /**
     * Calculate the complete GEO score for content.
     *
     * @param  string  $content  The HTML content to analyze
     * @param  array  $context  Additional context (url, team_id, embedding, etc.)
     * @return array Complete score breakdown with recommendations
     */
    public function score(string $content, array $context = []): array
    {
        $pillarResults = [];
        $totalScore = 0;
        $maxPossible = 0;

        foreach ($this->scorers as $key => $scorer) {
            $result = $scorer->score($content, $context);
            $pillarResults[$key] = [
                'name' => $scorer->getName(),
                'score' => $result['score'],
                'max_score' => $result['max_score'],
                'percentage' => round(($result['score'] / $result['max_score']) * 100, 1),
                'details' => $result['details'],
            ];

            $totalScore += $result['score'];
            $maxPossible += $result['max_score'];
        }

        $overallScore = round($totalScore, 1);
        $overallPercentage = round(($totalScore / $maxPossible) * 100, 1);

        return [
            'score' => $overallScore,
            'max_score' => $maxPossible,
            'percentage' => $overallPercentage,
            'grade' => $this->getGrade($overallPercentage),
            'pillars' => $pillarResults,
            'recommendations' => $this->generateRecommendations($pillarResults),
            'summary' => $this->generateSummary($overallScore, $pillarResults),
            'scored_at' => now()->toISOString(),
        ];
    }

    /**
     * Score only specific pillars.
     */
    public function scorePartial(string $content, array $pillars, array $context = []): array
    {
        $results = [];

        foreach ($pillars as $pillar) {
            if (isset($this->scorers[$pillar])) {
                $scorer = $this->scorers[$pillar];
                $result = $scorer->score($content, $context);
                $results[$pillar] = [
                    'name' => $scorer->getName(),
                    'score' => $result['score'],
                    'max_score' => $result['max_score'],
                    'percentage' => round(($result['score'] / $result['max_score']) * 100, 1),
                    'details' => $result['details'],
                ];
            }
        }

        return $results;
    }

    /**
     * Get a quick score without full details.
     */
    public function quickScore(string $content, array $context = []): array
    {
        $totalScore = 0;
        $maxPossible = 0;
        $pillarScores = [];

        foreach ($this->scorers as $key => $scorer) {
            $result = $scorer->score($content, $context);
            $pillarScores[$key] = [
                'score' => $result['score'],
                'max' => $result['max_score'],
            ];
            $totalScore += $result['score'];
            $maxPossible += $result['max_score'];
        }

        return [
            'score' => round($totalScore, 1),
            'percentage' => round(($totalScore / $maxPossible) * 100, 1),
            'grade' => $this->getGrade(($totalScore / $maxPossible) * 100),
            'pillars' => $pillarScores,
        ];
    }

    /**
     * Get grade based on percentage.
     */
    private function getGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 85 => 'A',
            $percentage >= 80 => 'A-',
            $percentage >= 75 => 'B+',
            $percentage >= 70 => 'B',
            $percentage >= 65 => 'B-',
            $percentage >= 60 => 'C+',
            $percentage >= 55 => 'C',
            $percentage >= 50 => 'C-',
            $percentage >= 45 => 'D+',
            $percentage >= 40 => 'D',
            default => 'F',
        };
    }

    /**
     * Generate actionable recommendations based on scores.
     */
    private function generateRecommendations(array $pillarResults): array
    {
        $recommendations = [];

        foreach ($pillarResults as $key => $pillar) {
            $percentage = $pillar['percentage'];

            // Only recommend for pillars scoring below 70%
            if ($percentage >= 70) {
                continue;
            }

            $recs = match ($key) {
                'definitions' => $this->getDefinitionRecommendations($pillar),
                'structure' => $this->getStructureRecommendations($pillar),
                'authority' => $this->getAuthorityRecommendations($pillar),
                'machine_readable' => $this->getMachineReadableRecommendations($pillar),
                'answerability' => $this->getAnswerabilityRecommendations($pillar),
                default => [],
            };

            if (! empty($recs)) {
                $recommendations[$key] = [
                    'pillar' => $pillar['name'],
                    'current_score' => $pillar['percentage'].'%',
                    'priority' => $percentage < 40 ? 'high' : ($percentage < 55 ? 'medium' : 'low'),
                    'actions' => $recs,
                ];
            }
        }

        // Sort by priority
        uasort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return $recommendations;
    }

    private function getDefinitionRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        if (empty($details['definitions_found'])) {
            $recs[] = 'Add a clear definition early in your content using phrases like "X is..." or "X refers to..."';
        }

        if (! ($details['early_definition'] ?? false)) {
            $recs[] = 'Move your primary definition to the first paragraph';
        }

        if (! ($details['entity_in_definition'] ?? false)) {
            $recs[] = 'Include your main topic/entity name in the definition sentence';
        }

        return $recs;
    }

    private function getStructureRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $headings = $details['headings'] ?? [];
        if (($headings['h1']['count'] ?? 0) !== 1) {
            $recs[] = 'Ensure exactly one H1 heading on the page';
        }

        if (($headings['h2']['count'] ?? 0) < 2) {
            $recs[] = 'Add more H2 subheadings to break up content into clear sections';
        }

        $lists = $details['lists'] ?? [];
        if (($lists['total_lists'] ?? 0) < 1) {
            $recs[] = 'Add bullet points or numbered lists to organize information';
        }

        $hierarchy = $details['hierarchy'] ?? [];
        if (! empty($hierarchy['violations'])) {
            $recs[] = 'Fix heading hierarchy: '.implode('; ', $hierarchy['violations']);
        }

        return $recs;
    }

    private function getAuthorityRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $depth = $details['topic_depth'] ?? [];
        if (($depth['word_count'] ?? 0) < 800) {
            $recs[] = 'Expand content depth - aim for at least 800-1500 words for comprehensive coverage';
        }

        if (($depth['total_indicators'] ?? 0) < 5) {
            $recs[] = 'Add examples, explanations, and evidence to demonstrate expertise';
        }

        $links = $details['internal_links'] ?? [];
        if (($links['internal_count'] ?? 0) < 3) {
            $recs[] = 'Add internal links to related content on your site';
        }

        return $recs;
    }

    private function getMachineReadableRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $schema = $details['schema'] ?? [];
        if (($schema['found'] ?? 0) === 0) {
            $recs[] = 'Add Schema.org structured data (JSON-LD recommended) - consider Article, FAQPage, or HowTo schemas';
        }

        $faq = $details['faq'] ?? [];
        if (! ($faq['has_faq_schema'] ?? false) && ($faq['question_count'] ?? 0) > 0) {
            $recs[] = 'You have FAQ-like content - add FAQPage schema markup';
        }

        $semantic = $details['semantic_html'] ?? [];
        if (($semantic['unique_elements_used'] ?? 0) < 2) {
            $recs[] = 'Use more semantic HTML elements (article, section, aside, figure)';
        }

        if (($semantic['images']['alt_coverage'] ?? 100) < 90) {
            $recs[] = 'Add descriptive alt text to all images';
        }

        return $recs;
    }

    private function getAnswerabilityRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $declarative = $details['declarative'] ?? [];
        if (($declarative['declarative_ratio'] ?? 0) < 0.5) {
            $recs[] = 'Use more declarative statements (e.g., "X is Y" instead of questions or vague phrasing)';
        }

        $uncertainty = $details['uncertainty'] ?? [];
        if (($uncertainty['hedging_count'] ?? 0) > 5) {
            $recs[] = 'Reduce hedging words like "maybe", "perhaps", "possibly" - be more definitive';
        }

        $snippets = $details['snippets'] ?? [];
        if (($snippets['count'] ?? 0) === 0) {
            $recs[] = 'Add concise, quotable sentences (50-150 chars) that directly answer questions';
        }

        $directness = $details['directness'] ?? [];
        if (! ($directness['starts_with_answer'] ?? false)) {
            $recs[] = 'Start with the answer, not preamble - avoid "In this article we will..."';
        }

        return $recs;
    }

    /**
     * Generate a human-readable summary.
     */
    private function generateSummary(float $score, array $pillarResults): array
    {
        // Find strongest and weakest pillars
        $sorted = $pillarResults;
        uasort($sorted, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        $strongest = array_slice($sorted, 0, 2);
        $weakest = array_slice(array_reverse($sorted), 0, 2);

        $strongestNames = array_map(fn ($p) => $p['name'], $strongest);
        $weakestNames = array_map(fn ($p) => $p['name'], $weakest);

        return [
            'overall' => $this->getOverallSummary($score),
            'strengths' => $strongestNames,
            'weaknesses' => $weakestNames,
            'focus_area' => $weakestNames[0] ?? null,
        ];
    }

    private function getOverallSummary(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent GEO optimization. Your content is well-positioned for AI search engines.',
            $score >= 70 => 'Good GEO score. Some improvements can further boost AI visibility.',
            $score >= 55 => 'Moderate GEO score. Focus on the recommended improvements.',
            $score >= 40 => 'Below average GEO score. Significant improvements needed.',
            default => 'Low GEO score. Content needs substantial optimization for AI engines.',
        };
    }

    /**
     * Get all available scorer instances.
     */
    public function getScorers(): array
    {
        return $this->scorers;
    }

    /**
     * Add a custom scorer.
     */
    public function addScorer(string $key, ScorerInterface $scorer): self
    {
        $this->scorers[$key] = $scorer;

        return $this;
    }
}
