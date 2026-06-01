<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * GEO (Generative Engine Optimization) Scorer
 *
 * Orchestrates all scoring pillars to produce a comprehensive
 * GEO score with explainable, actionable insights.
 *
 * Base Pillars (Free tier, 94 points):
 * - High-Confidence Answerability (30 points) — strongest citation predictor
 * - Topic Authority (22 points)
 * - Clear Definitions (20 points) — third strongest predictor
 * - Structured Knowledge (12 points)
 * - Machine-Readable Formatting (10 points)
 *
 * Pro Pillars (+35 points = 129 total):
 * - Citations & Sources (20 points) — second strongest predictor
 * - E-E-A-T Signals (10 points)
 * - AI Crawler Access (5 points)
 *
 * Full Pillars (+41 points = 170 total):
 * - Readability (18 points)
 * - Content Freshness (10 points)
 * - Question Coverage (8 points)
 * - Multimedia Content (5 points)
 *
 * Weights are calibrated from a two-phase empirical study of 60 websites
 * across 17 industries, measuring actual AI citation rates.
 */
class GeoScorer
{
    private array $scorers = [];

    private array $pillarTiers = [];

    public const TIER_FREE = 'free';

    public const TIER_PRO = 'pro';

    public const TIER_AGENCY = 'agency';

    /**
     * Resource links for each pillar type to help users learn more.
     */
    private const PILLAR_RESOURCES = [
        'definitions' => [
            ['title' => 'What Is GEO?', 'url' => '/blog/what-is-geo-complete-guide'],
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
        ],
        'structure' => [
            ['title' => 'Why SSR Matters for GEO', 'url' => '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility'],
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
        ],
        'authority' => [
            ['title' => 'How AI Search Engines Cite Sources', 'url' => '/blog/how-ai-search-engines-cite-sources'],
            ['title' => 'GEO vs SEO: Key Differences', 'url' => '/blog/geo-vs-seo-key-differences'],
        ],
        'machine_readable' => [
            ['title' => 'Why SSR Matters for GEO', 'url' => '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility'],
            ['title' => 'What Is GEO?', 'url' => '/blog/what-is-geo-complete-guide'],
        ],
        'answerability' => [
            ['title' => 'How AI Search Engines Cite Sources', 'url' => '/blog/how-ai-search-engines-cite-sources'],
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
        ],
        'eeat' => [
            ['title' => 'How AI Search Engines Cite Sources', 'url' => '/blog/how-ai-search-engines-cite-sources'],
            ['title' => 'GEO vs SEO: Key Differences', 'url' => '/blog/geo-vs-seo-key-differences'],
        ],
        'citations' => [
            ['title' => 'How AI Search Engines Cite Sources', 'url' => '/blog/how-ai-search-engines-cite-sources'],
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
        ],
        'ai_accessibility' => [
            ['title' => 'Why SSR Matters for GEO', 'url' => '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility'],
            ['title' => 'The Rise of AI Search', 'url' => '/blog/rise-of-ai-search-content-creators'],
        ],
        'freshness' => [
            ['title' => 'GEO vs SEO: Key Differences', 'url' => '/blog/geo-vs-seo-key-differences'],
            ['title' => 'The Rise of AI Search', 'url' => '/blog/rise-of-ai-search-content-creators'],
        ],
        'readability' => [
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
            ['title' => 'What Is GEO?', 'url' => '/blog/what-is-geo-complete-guide'],
        ],
        'question_coverage' => [
            ['title' => 'How AI Search Engines Cite Sources', 'url' => '/blog/how-ai-search-engines-cite-sources'],
            ['title' => '10 Ways to Optimize Content for AI', 'url' => '/blog/10-ways-optimize-content-chatgpt-perplexity'],
        ],
        'multimedia' => [
            ['title' => 'GEO vs SEO: Key Differences', 'url' => '/blog/geo-vs-seo-key-differences'],
            ['title' => 'What Is GEO?', 'url' => '/blog/what-is-geo-complete-guide'],
        ],
    ];

    public function __construct(
        ?DefinitionScorer $definitionScorer = null,
        ?StructureScorer $structureScorer = null,
        ?AuthorityScorer $authorityScorer = null,
        ?MachineReadableScorer $machineReadableScorer = null,
        ?AnswerabilityScorer $answerabilityScorer = null,
    ) {
        // Base pillars (Free tier)
        $this->scorers = [
            'definitions' => $definitionScorer ?? new DefinitionScorer,
            'structure' => $structureScorer ?? new StructureScorer,
            'authority' => $authorityScorer ?? new AuthorityScorer,
            'machine_readable' => $machineReadableScorer ?? new MachineReadableScorer,
            'answerability' => $answerabilityScorer ?? new AnswerabilityScorer,
        ];

        // Track which tier each pillar belongs to
        $this->pillarTiers = [
            'definitions' => self::TIER_FREE,
            'structure' => self::TIER_FREE,
            'authority' => self::TIER_FREE,
            'machine_readable' => self::TIER_FREE,
            'answerability' => self::TIER_FREE,
        ];
    }

    /**
     * Configure scorer for a specific plan tier.
     */
    public function forTier(string $tier): self
    {
        // Pro tier adds E-E-A-T, Citations, AI Accessibility
        if (in_array($tier, [self::TIER_PRO, self::TIER_AGENCY])) {
            $this->scorers['eeat'] = new EEATScorer;
            $this->scorers['citations'] = new CitationScorer;
            $this->scorers['ai_accessibility'] = new AIAccessibilityScorer;

            $this->pillarTiers['eeat'] = self::TIER_PRO;
            $this->pillarTiers['citations'] = self::TIER_PRO;
            $this->pillarTiers['ai_accessibility'] = self::TIER_PRO;
        }

        // Agency tier adds Freshness, Readability, Question Coverage, Multimedia
        if ($tier === self::TIER_AGENCY) {
            $this->scorers['freshness'] = new FreshnessScorer;
            $this->scorers['readability'] = new ReadabilityScorer;
            $this->scorers['question_coverage'] = new QuestionCoverageScorer;
            $this->scorers['multimedia'] = new MultimediaScorer;

            $this->pillarTiers['freshness'] = self::TIER_AGENCY;
            $this->pillarTiers['readability'] = self::TIER_AGENCY;
            $this->pillarTiers['question_coverage'] = self::TIER_AGENCY;
            $this->pillarTiers['multimedia'] = self::TIER_AGENCY;
        }

        return $this;
    }

    /**
     * Calculate the complete GEO score for content.
     *
     * @param  string  $content  The HTML content to analyze
     * @param  array  $context  Additional context (url, team_id, embedding, etc.)
     * @param  callable|null  $onPillarComplete  Callback after each pillar: fn(string $pillarKey) => void
     * @return array Complete score breakdown with recommendations
     */
    public function score(string $content, array $context = [], ?callable $onPillarComplete = null): array
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
                'tier' => $this->pillarTiers[$key] ?? self::TIER_FREE,
            ];

            $totalScore += $result['score'];
            $maxPossible += $result['max_score'];

            // Report progress after each pillar
            if ($onPillarComplete) {
                $onPillarComplete($key);
            }
        }

        $overallScore = round($totalScore, 1);
        $overallPercentage = round(($totalScore / $maxPossible) * 100, 1);

        return [
            'score' => $overallScore,
            'max_score' => $maxPossible,
            'percentage' => $overallPercentage,
            'grade' => $this->getGrade($overallPercentage),
            'citation_readiness' => $this->calculateCitationReadiness($pillarResults),
            'content_type' => $this->detectContentType($content, $context),
            'pillars' => $pillarResults,
            'recommendations' => $this->generateRecommendations($pillarResults),
            'summary' => $this->generateSummary($overallPercentage, $pillarResults),
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
                    'tier' => $this->pillarTiers[$pillar] ?? self::TIER_FREE,
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
                'tier' => $this->pillarTiers[$key] ?? self::TIER_FREE,
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
     * Calculate Citation Readiness Score from the three empirically-proven predictive pillars.
     *
     * Based on GeoSource.ai research: Answerability (+69% lift), Citations Quality (+56% lift),
     * and Definitions (+40% lift) are the strongest predictors of AI citation.
     * Sites scoring high on all three have a 51.6% citation rate vs 24.2% for sites scoring low.
     */
    private function calculateCitationReadiness(array $pillarResults): array
    {
        $predictivePillars = [
            'answerability' => ['weight' => 0.40, 'label' => 'Answerability'],
            'citations' => ['weight' => 0.35, 'label' => 'Citation Quality'],
            'definitions' => ['weight' => 0.25, 'label' => 'Definitions'],
        ];

        $weightedScore = 0;
        $totalWeight = 0;
        $factors = [];

        foreach ($predictivePillars as $key => $config) {
            if (isset($pillarResults[$key])) {
                $pct = $pillarResults[$key]['percentage'];
                $weightedScore += $pct * $config['weight'];
                $totalWeight += $config['weight'];
                $factors[$key] = [
                    'label' => $config['label'],
                    'score' => $pct,
                    'weight' => $config['weight'],
                    'status' => $pct >= 60 ? 'strong' : ($pct >= 40 ? 'moderate' : 'weak'),
                ];
            }
        }

        $score = $totalWeight > 0 ? round($weightedScore / $totalWeight, 1) : 0;

        return [
            'score' => $score,
            'grade' => $this->getCitationReadinessGrade($score),
            'factors' => $factors,
            'summary' => $this->getCitationReadinessSummary($score, $factors),
            'benchmark' => $this->getCitationBenchmark($score),
        ];
    }

    /**
     * Grade citation readiness on a simpler scale.
     */
    private function getCitationReadinessGrade(float $score): string
    {
        return match (true) {
            $score >= 70 => 'High',
            $score >= 50 => 'Moderate',
            $score >= 30 => 'Low',
            default => 'Very Low',
        };
    }

    /**
     * Generate a summary for citation readiness.
     */
    private function getCitationReadinessSummary(float $score, array $factors): string
    {
        $weakFactors = array_filter($factors, fn ($f) => $f['status'] === 'weak');

        if ($score >= 70) {
            return 'Your content has strong citation potential. AI platforms can confidently extract and cite this content.';
        }

        if ($score >= 50) {
            $weakNames = implode(' and ', array_map(fn ($f) => $f['label'], $weakFactors));

            return $weakNames
                ? "Moderate citation potential. Improving {$weakNames} would increase AI citation likelihood."
                : 'Moderate citation potential. Your content is citable but not optimally structured for AI extraction.';
        }

        if ($score >= 30) {
            return 'Low citation potential. AI platforms may struggle to extract confident, citable information from this content. Focus on direct answers, explicit definitions, and citing authoritative sources.';
        }

        return 'Very low citation potential. This content lacks the clarity, definitions, and source citations that AI platforms need to cite with confidence.';
    }

    /**
     * Provide citation rate benchmarks from the GeoSource.ai study.
     */
    private function getCitationBenchmark(float $score): array
    {
        // Based on our two-phase study of 60 websites across 17 industries
        if ($score >= 60) {
            return [
                'expected_citation_rate' => '49%',
                'comparison' => 'Top third — sites in this range are cited nearly 2x more than average',
                'study_basis' => 'Based on 60-site study across 17 industries',
            ];
        }

        if ($score >= 40) {
            return [
                'expected_citation_rate' => '38%',
                'comparison' => 'Middle third — citation rate close to average',
                'study_basis' => 'Based on 60-site study across 17 industries',
            ];
        }

        return [
            'expected_citation_rate' => '24%',
            'comparison' => 'Bottom third — focus on Answerability and Definitions to improve',
            'study_basis' => 'Based on 60-site study across 17 industries',
        ];
    }

    /**
     * Detect content type to provide industry context and query-type recommendations.
     */
    private function detectContentType(string $content, array $context): array
    {
        $plainText = strtolower(strip_tags($content));
        $url = $context['url'] ?? '';
        $domain = parse_url($url, PHP_URL_HOST) ?? '';

        // Detect content type from signals in the content and URL
        $signals = [
            'informational' => 0,
            'transactional' => 0,
            'educational' => 0,
        ];

        // Informational signals
        $infoPatterns = ['how to', 'what is', 'guide', 'learn', 'understand', 'explained', 'definition', 'overview', 'introduction', 'tutorial', 'step by step', 'tips for', 'best practices'];
        foreach ($infoPatterns as $pattern) {
            $signals['informational'] += substr_count($plainText, $pattern);
        }

        // Transactional signals
        $transPatterns = ['buy now', 'add to cart', 'pricing', 'free trial', 'sign up', 'get started', 'request demo', 'subscribe', 'order now', 'shop', 'purchase', 'starting at', 'per month', '/mo'];
        foreach ($transPatterns as $pattern) {
            $signals['transactional'] += substr_count($plainText, $pattern);
        }

        // Educational signals
        $eduPatterns = ['research', 'study', 'findings', 'methodology', 'data shows', 'according to', 'evidence', 'published', 'peer-reviewed', 'citation', 'reference'];
        foreach ($eduPatterns as $pattern) {
            $signals['educational'] += substr_count($plainText, $pattern);
        }

        // Determine primary type
        arsort($signals);
        $primaryType = array_key_first($signals);
        $confidence = max($signals) > 5 ? 'high' : (max($signals) > 2 ? 'medium' : 'low');

        // Industry benchmark data from our study
        $industryInsight = $this->getIndustryInsight($primaryType);

        return [
            'primary_type' => $primaryType,
            'confidence' => $confidence,
            'signals' => $signals,
            'insight' => $industryInsight,
        ];
    }

    /**
     * Provide industry-level citation context based on content type.
     */
    private function getIndustryInsight(string $contentType): array
    {
        return match ($contentType) {
            'informational' => [
                'citation_context' => 'Informational content has the highest citation potential.',
                'avg_citation_rate' => '77.8%',
                'recommendation' => 'AI platforms confidently cite factual, educational content. Focus on clear definitions, authoritative sources, and direct answers to common questions.',
                'top_industries' => 'Healthcare (80%), Travel (83%), Finance (67%)',
            ],
            'educational' => [
                'citation_context' => 'Educational and research content is highly citable.',
                'avg_citation_rate' => '65%',
                'recommendation' => 'Educational content performs well in AI search. Strengthen this by adding explicit definitions, citing primary sources, and structuring content as step-by-step guides.',
                'top_industries' => 'Education (25-58%), Finance (67%), Legal (50%)',
            ],
            'transactional' => [
                'citation_context' => 'Product and transactional pages have lower citation rates.',
                'avg_citation_rate' => '13.3%',
                'recommendation' => 'AI platforms are less confident recommending specific products. To increase citation likelihood: create educational content alongside product pages, publish comparison guides, and target informational queries ("how to choose X") rather than just product pages.',
                'top_industries' => 'SaaS (17%), Ecommerce (7%), Recruitment (0%)',
            ],
            default => [
                'citation_context' => 'Mixed content type detected.',
                'avg_citation_rate' => '40%',
                'recommendation' => 'Ensure your most important content clearly serves informational queries with direct answers and explicit definitions.',
                'top_industries' => 'Varies by industry',
            ],
        };
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
                'eeat' => $this->getEEATRecommendations($pillar),
                'citations' => $this->getCitationRecommendations($pillar),
                'ai_accessibility' => $this->getAIAccessibilityRecommendations($pillar),
                'freshness' => $this->getFreshnessRecommendations($pillar),
                'readability' => $this->getReadabilityRecommendations($pillar),
                'question_coverage' => $this->getQuestionCoverageRecommendations($pillar),
                'multimedia' => $this->getMultimediaRecommendations($pillar),
                default => [],
            };

            if (! empty($recs)) {
                $recommendations[$key] = [
                    'pillar' => $pillar['name'],
                    'current_score' => $pillar['percentage'].'%',
                    'priority' => $percentage < 40 ? 'high' : ($percentage < 55 ? 'medium' : 'low'),
                    'actions' => $recs,
                    'tier' => $pillar['tier'] ?? self::TIER_FREE,
                    'resources' => self::PILLAR_RESOURCES[$key] ?? [],
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

        $llmsTxt = $details['llms_txt'] ?? [];
        if (! ($llmsTxt['exists'] ?? false)) {
            $recs[] = 'Add an llms.txt file to your site root to help AI systems understand your content';
        } elseif (($llmsTxt['quality_score'] ?? 0) < 60) {
            $missingElements = [];
            if (! ($llmsTxt['has_description'] ?? false)) {
                $missingElements[] = 'description';
            }
            if (! ($llmsTxt['has_pages'] ?? false)) {
                $missingElements[] = 'page URLs';
            }
            if (! empty($missingElements)) {
                $recs[] = 'Improve llms.txt by adding: '.implode(', ', $missingElements);
            }
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

    // Pro tier recommendations

    private function getEEATRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $author = $details['author'] ?? [];
        if (! ($author['has_author'] ?? false)) {
            $recs[] = 'Add author attribution with name and link to author bio';
        }
        if (! ($author['has_author_bio'] ?? false)) {
            $recs[] = 'Include an author bio section with credentials and expertise';
        }

        $trust = $details['trust_signals'] ?? [];
        if (! ($trust['has_reviews'] ?? false) && ! ($trust['has_testimonials'] ?? false)) {
            $recs[] = 'Add social proof like reviews, testimonials, or case studies';
        }

        $contact = $details['contact'] ?? [];
        if (! ($contact['has_contact_page_link'] ?? false)) {
            $recs[] = 'Ensure visible contact information or link to contact page';
        }

        $credentials = $details['credentials'] ?? [];
        if (! ($credentials['has_expertise_claims'] ?? false)) {
            $recs[] = 'Highlight relevant expertise, experience, or qualifications';
        }

        return $recs;
    }

    private function getCitationRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $links = $details['external_links'] ?? [];
        if (($links['authoritative_count'] ?? 0) < 2) {
            $recs[] = 'Add links to authoritative sources (.gov, .edu, research papers, reputable publications)';
        }

        $citations = $details['citations'] ?? [];
        if (! ($citations['has_inline_citations'] ?? false)) {
            $recs[] = 'Use inline citations like "according to [source]" or "research shows that..."';
        }

        $stats = $details['statistics'] ?? [];
        if (! ($stats['has_statistics'] ?? false)) {
            $recs[] = 'Include relevant statistics and data points with sources';
        }

        $refs = $details['references'] ?? [];
        if (! ($refs['has_reference_section'] ?? false) && ($links['total_external'] ?? 0) > 3) {
            $recs[] = 'Consider adding a References or Sources section for credibility';
        }

        return $recs;
    }

    private function getAIAccessibilityRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $robots = $details['robots_txt'] ?? [];
        if (! ($robots['allows_all_ai'] ?? true)) {
            $blockedBots = $robots['blocked_bots'] ?? [];
            if (! empty($blockedBots)) {
                $recs[] = 'Your robots.txt blocks AI crawlers: '.implode(', ', array_slice($blockedBots, 0, 3)).'. Consider allowing them for better AI visibility.';
            }
        }

        if (! ($robots['has_sitemap'] ?? false)) {
            $recs[] = 'Add a Sitemap reference to your robots.txt';
        }

        $meta = $details['meta_robots'] ?? [];
        if ($meta['noindex'] ?? false) {
            $recs[] = 'Remove noindex directive to allow indexing by AI systems';
        }
        if ($meta['nosnippet'] ?? false) {
            $recs[] = 'Remove nosnippet directive to allow AI systems to use content in responses';
        }

        return $recs;
    }

    // Agency tier recommendations

    private function getFreshnessRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $dates = $details['dates'] ?? [];
        if (! ($dates['has_publish_date'] ?? false)) {
            $recs[] = 'Add a visible publication date to your content';
        }

        if (! ($dates['has_modified_date'] ?? false)) {
            $recs[] = 'Show "Last updated" date, especially for evergreen content';
        }

        $age = $dates['age_category'] ?? 'unknown';
        if (in_array($age, ['aging', 'stale'])) {
            $recs[] = 'Content appears outdated - review and update with current information';
        }

        $schema = $details['schema_dates'] ?? [];
        if (! ($schema['has_date_published'] ?? false)) {
            $recs[] = 'Add datePublished to your Schema.org structured data';
        }

        $temporal = $details['temporal_references'] ?? [];
        if (! ($temporal['current_year_mentioned'] ?? false)) {
            $recs[] = 'Include current year references where appropriate (e.g., "in 2024")';
        }

        return $recs;
    }

    private function getReadabilityRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $fk = $details['flesch_kincaid'] ?? [];
        $readingLevel = $fk['reading_level'] ?? 'unknown';

        if (in_array($readingLevel, ['hard', 'very_hard'])) {
            $recs[] = 'Simplify language - aim for 8th-9th grade reading level for broader accessibility';
        }

        $sentences = $details['sentence_analysis'] ?? [];
        if (($sentences['avg_length'] ?? 0) > 25) {
            $recs[] = 'Break up long sentences - aim for 15-20 words per sentence on average';
        }

        if (($sentences['very_long_sentences'] ?? 0) > 3) {
            $recs[] = 'Reduce very long sentences (35+ words) - they\'re harder for AI to parse';
        }

        $paragraphs = $details['paragraph_analysis'] ?? [];
        if (($paragraphs['avg_length'] ?? 0) > 150) {
            $recs[] = 'Break up long paragraphs - aim for 50-100 words per paragraph for web readability';
        }

        $words = $details['word_analysis'] ?? [];
        if (($words['complex_ratio'] ?? 0) > 30) {
            $recs[] = 'Reduce complex words (3+ syllables) - use simpler alternatives where possible';
        }

        return $recs;
    }

    private function getQuestionCoverageRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $questions = $details['questions'] ?? [];
        if (count($questions['heading_questions'] ?? []) < 2) {
            $recs[] = 'Use question-format headings (e.g., "What is X?" or "How do I Y?")';
        }

        $patterns = $details['qa_patterns'] ?? [];
        if (! ($patterns['has_faq_section'] ?? false)) {
            $recs[] = 'Add a FAQ section to address common questions';
        }

        if (! ($patterns['has_qa_schema'] ?? false) && ($patterns['has_question_headings'] ?? false)) {
            $recs[] = 'Add FAQPage schema markup for your Q&A content';
        }

        $anticipation = $details['anticipation'] ?? [];
        $coverage = $anticipation['coverage_score'] ?? 0;
        if ($coverage < 50) {
            $missing = [];
            if (! ($anticipation['covers_what'] ?? false)) {
                $missing[] = '"What is..."';
            }
            if (! ($anticipation['covers_how'] ?? false)) {
                $missing[] = '"How to..."';
            }
            if (! ($anticipation['covers_why'] ?? false)) {
                $missing[] = '"Why..."';
            }
            if (! empty($missing)) {
                $recs[] = 'Cover more question types: '.implode(', ', $missing);
            }
        }

        return $recs;
    }

    private function getMultimediaRecommendations(array $pillar): array
    {
        $recs = [];
        $details = $pillar['details'];

        $images = $details['images'] ?? [];
        if (($images['total_images'] ?? 0) < 2) {
            $recs[] = 'Add relevant images to break up text and illustrate concepts';
        }

        if (($images['alt_quality'] ?? 'none') === 'poor' || ($images['alt_quality'] ?? 'none') === 'none') {
            $recs[] = 'Add descriptive alt text to all images for accessibility and AI understanding';
        }

        if (($images['images_with_caption'] ?? 0) === 0 && ($images['total_images'] ?? 0) > 0) {
            $recs[] = 'Add captions to images using <figure> and <figcaption> elements';
        }

        $tables = $details['tables'] ?? [];
        if (! ($tables['has_tables'] ?? false)) {
            $recs[] = 'Consider using tables to present comparative data or structured information';
        }

        $visuals = $details['visual_elements'] ?? [];
        if (($visuals['visual_variety'] ?? 0) < 2) {
            $recs[] = 'Add visual variety: consider diagrams, callouts, or code blocks where appropriate';
        }

        return $recs;
    }

    /**
     * Generate a human-readable summary.
     */
    private function generateSummary(float $percentage, array $pillarResults): array
    {
        // Find strongest and weakest pillars (only from base pillars for consistency)
        $basePillars = array_filter($pillarResults, fn ($p) => ($p['tier'] ?? self::TIER_FREE) === self::TIER_FREE);

        $sorted = $basePillars;
        uasort($sorted, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        $strongest = array_slice($sorted, 0, 2);
        $weakest = array_slice(array_reverse($sorted), 0, 2);

        $strongestNames = array_map(fn ($p) => $p['name'], $strongest);
        $weakestNames = array_map(fn ($p) => $p['name'], $weakest);

        return [
            'overall' => $this->getOverallSummary($percentage),
            'strengths' => $strongestNames,
            'weaknesses' => $weakestNames,
            'focus_area' => $weakestNames[0] ?? null,
        ];
    }

    private function getOverallSummary(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'Excellent GEO optimization. Your content is well-positioned for AI search engines.',
            $percentage >= 70 => 'Good GEO score. Some improvements can further boost AI visibility.',
            $percentage >= 55 => 'Moderate GEO score. Focus on the recommended improvements.',
            $percentage >= 40 => 'Below average GEO score. Significant improvements needed.',
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
    public function addScorer(string $key, ScorerInterface $scorer, string $tier = self::TIER_FREE): self
    {
        $this->scorers[$key] = $scorer;
        $this->pillarTiers[$key] = $tier;

        return $this;
    }

    /**
     * Get the tier for a specific pillar.
     */
    public function getPillarTier(string $key): string
    {
        return $this->pillarTiers[$key] ?? self::TIER_FREE;
    }
}
