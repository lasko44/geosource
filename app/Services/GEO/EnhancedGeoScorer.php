<?php

namespace App\Services\GEO;

use App\Services\RAG\EmbeddingService;
use App\Services\RAG\RAGService;
use App\Services\RAG\VectorStore;

/**
 * Enhanced GEO Scorer with RAG Integration
 *
 * Extends the base GEO scorer with:
 * - Semantic similarity analysis using pgvector
 * - LLM-powered content analysis
 * - Competitive benchmarking
 * - AI-generated improvement suggestions
 */
class EnhancedGeoScorer
{
    public function __construct(
        private GeoScorer $baseScorer,
        private RAGService $ragService,
        private VectorStore $vectorStore,
        private EmbeddingService $embeddingService,
    ) {}

    /**
     * Perform comprehensive GEO analysis with RAG enhancement.
     */
    public function analyze(
        string $content,
        int $teamId,
        array $options = []
    ): array {
        // Get base GEO score
        $context = [
            'team_id' => $teamId,
            'url' => $options['url'] ?? null,
        ];

        // Generate embedding for similarity search
        $embedding = $this->embeddingService->embed($content);
        $context['embedding'] = $embedding;

        $baseScore = $this->baseScorer->score($content, $context);

        // Find similar content for benchmarking
        $similarContent = $this->vectorStore->searchByVector(
            $embedding,
            $teamId,
            limit: config('rag.geo.comparison_limit', 5),
            threshold: config('rag.geo.min_similarity_for_comparison', 0.4)
        );

        // Calculate competitive position
        $benchmark = $this->calculateBenchmark($baseScore, $similarContent, $teamId);

        // Enhance with RAG analysis if enabled
        $ragAnalysis = null;
        $aiSuggestions = null;

        if (config('rag.geo.use_rag_analysis', true) && ! empty(config('rag.openai.api_key'))) {
            try {
                $ragAnalysis = $this->ragService->analyzeForGEO($content, $teamId);
                $aiSuggestions = $this->generateAISuggestions($content, $baseScore, $teamId);
            } catch (\Exception $e) {
                $ragAnalysis = ['error' => $e->getMessage()];
            }
        }

        return [
            'score' => $baseScore['score'],
            'max_score' => $baseScore['max_score'],
            'percentage' => $baseScore['percentage'],
            'grade' => $baseScore['grade'],
            'pillars' => $baseScore['pillars'],
            'recommendations' => $baseScore['recommendations'],
            'summary' => $baseScore['summary'],
            'benchmark' => $benchmark,
            'similar_content' => $similarContent->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'similarity' => round($doc->similarity, 4),
            ])->toArray(),
            'rag_analysis' => $ragAnalysis,
            'ai_suggestions' => $aiSuggestions,
            'embedding_generated' => true,
            'scored_at' => now()->toISOString(),
        ];
    }

    /**
     * Perform quick analysis without LLM calls.
     */
    public function quickAnalyze(string $content, int $teamId): array
    {
        $embedding = $this->embeddingService->embed($content);

        $baseScore = $this->baseScorer->quickScore($content, [
            'team_id' => $teamId,
            'embedding' => $embedding,
        ]);

        // Quick similarity check
        $topSimilar = $this->vectorStore->searchByVector(
            $embedding,
            $teamId,
            limit: 3,
            threshold: 0.5
        );

        return array_merge($baseScore, [
            'similar_count' => $topSimilar->count(),
            'avg_similarity' => $topSimilar->avg('similarity') ?? 0,
        ]);
    }

    /**
     * Compare content against competitors.
     */
    public function compareWithCompetitors(
        string $content,
        int $teamId,
        array $competitorUrls = []
    ): array {
        $myScore = $this->analyze($content, $teamId);

        $comparisons = [];

        // Compare with similar content in database
        foreach ($myScore['similar_content'] as $similar) {
            $doc = \App\Models\Document::find($similar['id']);
            if ($doc) {
                $competitorScore = $this->baseScorer->quickScore($doc->content, [
                    'team_id' => $teamId,
                ]);

                $comparisons[] = [
                    'title' => $similar['title'],
                    'similarity' => $similar['similarity'],
                    'their_score' => $competitorScore['score'],
                    'score_difference' => $myScore['score'] - $competitorScore['score'],
                    'pillar_comparison' => $this->comparePillars(
                        $myScore['pillars'],
                        $competitorScore['pillars']
                    ),
                ];
            }
        }

        return [
            'my_score' => $myScore['score'],
            'my_grade' => $myScore['grade'],
            'comparisons' => $comparisons,
            'position' => $this->calculatePosition($myScore['score'], $comparisons),
            'opportunities' => $this->identifyOpportunities($myScore, $comparisons),
        ];
    }

    /**
     * Track score changes over time.
     */
    public function trackProgress(
        string $contentId,
        string $content,
        int $teamId
    ): array {
        $currentScore = $this->analyze($content, $teamId);

        // Get historical scores from metadata
        $historical = \App\Models\Document::where('team_id', $teamId)
            ->whereRaw("metadata->>'content_id' = ?", [$contentId])
            ->whereRaw("metadata->>'type' = 'geo_score_history'")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $trend = 'stable';
        $change = 0;

        if ($historical->isNotEmpty()) {
            $previousScore = $historical->first()->metadata['score'] ?? 0;
            $change = $currentScore['score'] - $previousScore;

            if ($change > 2) {
                $trend = 'improving';
            } elseif ($change < -2) {
                $trend = 'declining';
            }
        }

        // Store current score for tracking
        \App\Models\Document::create([
            'team_id' => $teamId,
            'title' => "GEO Score: {$contentId}",
            'content' => json_encode($currentScore['pillars']),
            'metadata' => [
                'type' => 'geo_score_history',
                'content_id' => $contentId,
                'score' => $currentScore['score'],
                'grade' => $currentScore['grade'],
                'recorded_at' => now()->toISOString(),
            ],
        ]);

        return [
            'current' => $currentScore,
            'historical' => $historical->map(fn ($h) => [
                'score' => $h->metadata['score'] ?? 0,
                'grade' => $h->metadata['grade'] ?? 'N/A',
                'recorded_at' => $h->metadata['recorded_at'] ?? $h->created_at,
            ])->toArray(),
            'trend' => $trend,
            'change' => round($change, 1),
        ];
    }

    /**
     * Generate content optimized for GEO.
     */
    public function generateOptimizedContent(
        string $topic,
        int $teamId,
        array $options = []
    ): array {
        // Get existing high-performing content for reference
        $references = $this->vectorStore->search(
            $topic,
            $teamId,
            limit: 5,
            threshold: 0.3
        );

        $prompt = $this->buildContentGenerationPrompt($topic, $references, $options);

        try {
            $response = $this->callLLM($prompt, 3000, 0.4);

            // Score the generated content
            $generatedScore = $this->baseScorer->quickScore($response['content']);

            return [
                'content' => $response['content'],
                'estimated_score' => $generatedScore['score'],
                'estimated_grade' => $generatedScore['grade'],
                'references_used' => $references->count(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'content' => null,
            ];
        }
    }

    /**
     * Calculate benchmark against similar content.
     */
    private function calculateBenchmark(array $baseScore, $similarContent, int $teamId): array
    {
        if ($similarContent->isEmpty()) {
            return [
                'position' => 'unknown',
                'percentile' => null,
                'avg_similar_score' => null,
                'comparison' => 'No similar content found for comparison',
            ];
        }

        // Calculate average score of similar content
        $similarScores = [];
        foreach ($similarContent as $doc) {
            $score = $this->baseScorer->quickScore($doc->content, ['team_id' => $teamId]);
            $similarScores[] = $score['score'];
        }

        $avgSimilarScore = array_sum($similarScores) / count($similarScores);
        $scoreDiff = $baseScore['score'] - $avgSimilarScore;

        // Calculate percentile
        $belowCount = count(array_filter($similarScores, fn ($s) => $s < $baseScore['score']));
        $percentile = round(($belowCount / count($similarScores)) * 100);

        $position = match (true) {
            $scoreDiff >= 10 => 'leader',
            $scoreDiff >= 5 => 'above_average',
            $scoreDiff >= -5 => 'average',
            $scoreDiff >= -10 => 'below_average',
            default => 'needs_improvement',
        };

        return [
            'position' => $position,
            'percentile' => $percentile,
            'avg_similar_score' => round($avgSimilarScore, 1),
            'score_difference' => round($scoreDiff, 1),
            'comparison' => $this->getBenchmarkDescription($position, $scoreDiff),
        ];
    }

    private function getBenchmarkDescription(string $position, float $diff): string
    {
        return match ($position) {
            'leader' => "Excellent! Your content scores ".abs($diff)." points above similar content.",
            'above_average' => "Good performance. You're ".abs($diff)." points above the average.",
            'average' => 'Your content performs similarly to comparable content in your knowledge base.',
            'below_average' => "There's room for improvement. You're ".abs($diff)." points below average.",
            'needs_improvement' => "Significant optimization needed. You're ".abs($diff)." points below similar content.",
            default => 'Unable to determine benchmark position.',
        };
    }

    private function generateAISuggestions(string $content, array $baseScore, int $teamId): array
    {
        try {
            $result = $this->ragService->suggestImprovements($content, $baseScore, $teamId);

            return $result['suggestions'] ?? [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function comparePillars(array $myPillars, array $theirPillars): array
    {
        $comparison = [];

        foreach ($myPillars as $key => $myPillar) {
            $theirPillar = $theirPillars[$key] ?? null;

            if ($theirPillar) {
                $comparison[$key] = [
                    'my_score' => $myPillar['score'],
                    'their_score' => $theirPillar['score'],
                    'difference' => $myPillar['score'] - $theirPillar['score'],
                    'winner' => $myPillar['score'] > $theirPillar['score'] ? 'me' : ($myPillar['score'] < $theirPillar['score'] ? 'them' : 'tie'),
                ];
            }
        }

        return $comparison;
    }

    private function calculatePosition(float $myScore, array $comparisons): array
    {
        if (empty($comparisons)) {
            return ['rank' => 1, 'total' => 1, 'percentile' => 100];
        }

        $allScores = array_merge([$myScore], array_column($comparisons, 'their_score'));
        rsort($allScores);

        $rank = array_search($myScore, $allScores) + 1;
        $total = count($allScores);
        $percentile = round((($total - $rank) / $total) * 100);

        return [
            'rank' => $rank,
            'total' => $total,
            'percentile' => $percentile,
        ];
    }

    private function identifyOpportunities(array $myScore, array $comparisons): array
    {
        $opportunities = [];

        // Find pillars where competitors score higher
        foreach ($comparisons as $comp) {
            foreach ($comp['pillar_comparison'] ?? [] as $pillar => $data) {
                if ($data['winner'] === 'them' && $data['difference'] < -2) {
                    $opportunities[] = [
                        'pillar' => $pillar,
                        'gap' => abs($data['difference']),
                        'competitor' => $comp['title'],
                        'suggestion' => "Improve {$pillar} to match competitor's level",
                    ];
                }
            }
        }

        // Sort by gap size
        usort($opportunities, fn ($a, $b) => $b['gap'] <=> $a['gap']);

        return array_slice($opportunities, 0, 5);
    }

    private function buildContentGenerationPrompt(string $topic, $references, array $options): string
    {
        $style = $options['style'] ?? 'informative';
        $length = $options['length'] ?? 'medium';
        $audience = $options['audience'] ?? 'general';

        $refExamples = $references->take(3)->map(fn ($r) => "Example: {$r->title}\n".substr($r->content, 0, 500))->join("\n\n---\n\n");

        return <<<PROMPT
Generate content about "{$topic}" optimized for Generative Engine Optimization (GEO).

REQUIREMENTS:
- Style: {$style}
- Length: {$length} (short: 300-500 words, medium: 800-1200 words, long: 1500-2500 words)
- Audience: {$audience}

GEO OPTIMIZATION RULES:
1. Start with a clear definition in the first paragraph
2. Use descriptive H2 and H3 headings
3. Include bullet points or numbered lists
4. Use declarative, confident language
5. Avoid hedging words (maybe, perhaps, might)
6. Include quotable sentences that AI can cite directly
7. Structure content with clear sections
8. Add examples and evidence where appropriate

REFERENCE CONTENT (for style guidance):
{$refExamples}

Generate the optimized content now:
PROMPT;
    }

    private function callLLM(string $prompt, int $maxTokens = 1000, float $temperature = 0.3): array
    {
        $response = \Illuminate\Support\Facades\Http::withToken(config('rag.openai.api_key'))
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('rag.llm.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('LLM API error: '.$response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'],
            'usage' => $data['usage'],
        ];
    }
}
