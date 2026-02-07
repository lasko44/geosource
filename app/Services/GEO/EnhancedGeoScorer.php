<?php

namespace App\Services\GEO;

use App\Models\Document;
use App\Models\Scan;
use App\Services\RAG\ContentExtractor;
use App\Services\RAG\EmbeddingService;
use App\Services\RAG\RAGService;
use App\Services\RAG\VectorStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced GEO Scorer with RAG Integration
 *
 * Extends the base GEO scorer with:
 * - Semantic similarity analysis using pgvector
 * - LLM-powered content analysis with score integration
 * - Competitive benchmarking with threshold logic
 * - AI-generated improvement suggestions
 */
class EnhancedGeoScorer
{
    private bool $contentExtractionEnabled;

    /**
     * Minimum scans required before showing benchmark comparisons.
     */
    private const MIN_SCANS_FOR_BENCHMARK = 3;


    public function __construct(
        private GeoScorer $baseScorer,
        private RAGService $ragService,
        private VectorStore $vectorStore,
        private EmbeddingService $embeddingService,
        private ContentExtractor $contentExtractor,
    ) {
        $this->contentExtractionEnabled = config('rag.extraction.enabled', true);
    }

    /**
     * Configure scorer for a specific plan tier.
     */
    public function forTier(string $tier): self
    {
        $this->baseScorer->forTier($tier);

        return $this;
    }

    /**
     * Get isolation ID for embedding cache (prefers team_id, falls back to user_id).
     */
    private function getIsolationId(?int $teamId, ?int $userId): int
    {
        return $teamId ?? $userId;
    }

    /**
     * Perform comprehensive GEO analysis with RAG enhancement.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation (fallback when no team)
     * @param  callable|null  $onProgress  Callback for progress updates: fn(string $step) => void
     */
    public function analyze(
        string $content,
        ?int $teamId,
        ?int $userId = null,
        array $options = [],
        ?callable $onProgress = null
    ): array {
        $reportProgress = fn (string $step) => $onProgress ? $onProgress($step) : null;

        // Extract main content from HTML for better embedding quality
        $contentForEmbedding = $content;
        if ($this->contentExtractionEnabled) {
            $extracted = $this->contentExtractor->extractWithMetadata($content);
            $contentForEmbedding = Arr::get($extracted, 'content', $content);

            // Use extracted title if not provided
            $extractedTitle = Arr::get($extracted, 'title');
            if (empty(Arr::get($options, 'title')) && ! empty($extractedTitle)) {
                $options['title'] = $extractedTitle;
            }
        }

        // Get base GEO score (uses original HTML for structure analysis)
        $context = [
            'team_id' => $teamId,
            'user_id' => $userId,
            'url' => Arr::get($options, 'url'),
        ];

        // Generate embedding from extracted content (not raw HTML)
        $isolationId = $this->getIsolationId($teamId, $userId);
        $embedding = $this->embeddingService->embed($contentForEmbedding, true, $isolationId);
        $context['embedding'] = $embedding;

        $reportProgress('scoring_pillars');

        // Score all pillars - the baseScorer handles internal progress
        $baseScore = $this->baseScorer->score($content, $context);

        // Pillar scoring complete - move to next phase
        $reportProgress('scoring_final');
        $reportProgress('finding_similar');

        // Count existing scans for threshold checks
        $scanCounts = $this->getScanCounts($teamId, $userId);
        $hasSufficientData = Arr::get($scanCounts, 'total', 0) >= self::MIN_SCANS_FOR_BENCHMARK;

        // Get own content scans for benchmarking (from Scan model)
        $ownContentScans = $this->getOwnContentScans($teamId, $userId);

        // Get competitor scans for benchmarking (from Scan model)
        $competitorScans = $this->getCompetitorScans($teamId, $userId);

        // Calculate benchmark using Scan data (only if sufficient data)
        $benchmark = $hasSufficientData
            ? $this->calculateBenchmarkFromScans($baseScore, $ownContentScans)
            : $this->getInsufficientDataBenchmark($scanCounts);

        // Calculate competitor benchmark if competitors exist
        $competitorBenchmark = $competitorScans->isNotEmpty()
            ? $this->calculateCompetitorBenchmarkFromScans($baseScore, $competitorScans)
            : null;

        // Also get vector store content for AI analysis if available
        $ownContent = collect();
        $competitorContent = collect();
        try {
            $ownContent = $this->vectorStore->searchByVector(
                $embedding,
                $teamId,
                $userId,
                limit: config('rag.geo.comparison_limit', 5),
                threshold: config('rag.geo.min_similarity_for_comparison', 0.4),
                filters: ['is_competitor' => false]
            );
            $competitorContent = $this->vectorStore->searchByVector(
                $embedding,
                $teamId,
                $userId,
                limit: config('rag.geo.comparison_limit', 5),
                threshold: config('rag.geo.min_similarity_for_comparison', 0.4),
                filters: ['is_competitor' => true]
            );
        } catch (\Exception $e) {
            // Vector store not available, continue with Scan-based benchmarks
        }

        $reportProgress('generating_recommendations');

        // Run all AI analysis in parallel for speed
        $aiResults = $this->runParallelAIAnalysis($content, $baseScore, $teamId, $userId);
        $ragAnalysis = Arr::get($aiResults, 'rag_analysis');
        $aiSuggestions = Arr::get($aiResults, 'ai_suggestions');
        $citationReadiness = Arr::get($aiResults, 'citation_readiness');

        $reportProgress('calculating_benchmarks');

        // Use base score directly (no AI adjustment - citation readiness is separate)
        $baseScoreValue = Arr::get($baseScore, 'score', 0);
        $maxScore = Arr::get($baseScore, 'max_score', 100);
        $finalScore = round($baseScoreValue, 1);
        $finalGrade = $this->calculateGrade($finalScore, $maxScore);

        return [
            'score' => $finalScore,
            'max_score' => $maxScore,
            'percentage' => round(($finalScore / $maxScore) * 100, 1),
            'grade' => $finalGrade,
            'pillars' => Arr::get($baseScore, 'pillars', []),
            'recommendations' => Arr::get($baseScore, 'recommendations', []),
            'summary' => Arr::get($baseScore, 'summary'),
            'benchmark' => $benchmark,
            'competitor_benchmark' => $competitorBenchmark,
            'similar_content' => $ownContent->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'similarity' => round($doc->similarity, 4),
                'is_competitor' => false,
            ])->toArray(),
            'competitor_content' => $competitorContent->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'similarity' => round($doc->similarity, 4),
                'is_competitor' => true,
            ])->toArray(),
            'scan_counts' => $scanCounts,
            'has_sufficient_data' => $hasSufficientData,
            'rag_analysis' => $ragAnalysis,
            'ai_suggestions' => $aiSuggestions,
            'citation_readiness' => $citationReadiness,
            'embedding_generated' => true,
            'scored_at' => now()->toISOString(),
        ];
    }

    /**
     * Get scan counts for a team/user to determine benchmark availability.
     * Uses the Scan model directly for accurate counting.
     */
    private function getScanCounts(?int $teamId, ?int $userId): array
    {
        $query = Scan::where('status', 'completed');

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId)->whereNull('team_id');
        }

        $total = $query->count();

        // Count own content (non-competitor scans)
        $ownContent = Scan::where('status', 'completed')
            ->where('is_competitor', false)
            ->when($teamId !== null, fn ($q) => $q->where('team_id', $teamId))
            ->when($teamId === null, fn ($q) => $q->where('user_id', $userId)->whereNull('team_id'))
            ->count();

        // Count competitors
        $competitors = Scan::where('status', 'completed')
            ->where('is_competitor', true)
            ->when($teamId !== null, fn ($q) => $q->where('team_id', $teamId))
            ->when($teamId === null, fn ($q) => $q->where('user_id', $userId)->whereNull('team_id'))
            ->count();

        return [
            'total' => $total,
            'own_content' => $ownContent,
            'competitors' => $competitors,
            'min_required' => self::MIN_SCANS_FOR_BENCHMARK,
        ];
    }

    /**
     * Get own content (non-competitor) scans for benchmarking.
     */
    private function getOwnContentScans(?int $teamId, ?int $userId): \Illuminate\Support\Collection
    {
        return Scan::where('status', 'completed')
            ->where('is_competitor', false)
            ->whereNotNull('score')
            ->when($teamId !== null, fn ($q) => $q->where('team_id', $teamId))
            ->when($teamId === null, fn ($q) => $q->where('user_id', $userId)->whereNull('team_id'))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'url', 'title', 'score', 'grade', 'created_at']);
    }

    /**
     * Get competitor scans for benchmarking.
     */
    private function getCompetitorScans(?int $teamId, ?int $userId): \Illuminate\Support\Collection
    {
        return Scan::where('status', 'completed')
            ->where('is_competitor', true)
            ->whereNotNull('score')
            ->when($teamId !== null, fn ($q) => $q->where('team_id', $teamId))
            ->when($teamId === null, fn ($q) => $q->where('user_id', $userId)->whereNull('team_id'))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'url', 'title', 'score', 'grade', 'created_at']);
    }

    /**
     * Calculate benchmark against own content scans.
     */
    private function calculateBenchmarkFromScans(array $baseScore, \Illuminate\Support\Collection $scans): array
    {
        if ($scans->isEmpty()) {
            return [
                'position' => 'insufficient_data',
                'percentile' => null,
                'avg_similar_score' => null,
                'score_difference' => null,
                'comparison' => 'No completed scans found for comparison.',
                'scans_until_benchmark' => self::MIN_SCANS_FOR_BENCHMARK,
            ];
        }

        $scores = $scans->pluck('score')->filter()->values()->toArray();
        if (empty($scores)) {
            return $this->getInsufficientDataBenchmark(['total' => 0]);
        }

        $avgScore = array_sum($scores) / count($scores);
        $currentScore = Arr::get($baseScore, 'score', 0);
        $scoreDiff = $currentScore - $avgScore;

        // Calculate percentile
        $belowCount = count(array_filter($scores, fn ($s) => $s < $currentScore));
        $percentile = count($scores) > 0 ? round(($belowCount / count($scores)) * 100) : 0;

        $position = match (true) {
            $scoreDiff >= 10 => 'top_performer',
            $scoreDiff >= 5 => 'above_average',
            $scoreDiff >= -5 => 'average',
            $scoreDiff >= -10 => 'below_average',
            default => 'needs_improvement',
        };

        return [
            'position' => $position,
            'percentile' => $percentile,
            'avg_similar_score' => round($avgScore, 1),
            'score_difference' => round($scoreDiff, 1),
            'scans_analyzed' => count($scores),
            'comparison' => $this->getBenchmarkDescription($position, $scoreDiff),
        ];
    }

    /**
     * Calculate benchmark against competitor scans.
     */
    private function calculateCompetitorBenchmarkFromScans(array $baseScore, \Illuminate\Support\Collection $scans): array
    {
        if ($scans->isEmpty()) {
            return [
                'position' => 'no_competitors',
                'percentile' => null,
                'avg_competitor_score' => null,
                'comparison' => 'No competitor scans found for comparison. Mark scans as competitors to enable benchmarking.',
            ];
        }

        $scores = $scans->pluck('score')->filter()->values()->toArray();
        if (empty($scores)) {
            return [
                'position' => 'no_competitors',
                'percentile' => null,
                'avg_competitor_score' => null,
                'comparison' => 'No competitor scores available for comparison.',
            ];
        }

        $avgScore = array_sum($scores) / count($scores);
        $currentScore = Arr::get($baseScore, 'score', 0);
        $scoreDiff = $currentScore - $avgScore;

        // Calculate percentile (how many competitors you beat)
        $belowCount = count(array_filter($scores, fn ($s) => $s < $currentScore));
        $percentile = round(($belowCount / count($scores)) * 100);

        $position = match (true) {
            $scoreDiff >= 15 => 'dominant',
            $scoreDiff >= 5 => 'ahead',
            $scoreDiff >= -5 => 'competitive',
            $scoreDiff >= -15 => 'behind',
            default => 'far_behind',
        };

        return [
            'position' => $position,
            'percentile' => $percentile,
            'avg_competitor_score' => round($avgScore, 1),
            'score_difference' => round($scoreDiff, 1),
            'competitors_analyzed' => count($scores),
            'comparison' => $this->getCompetitorBenchmarkDescription($position, $scoreDiff, $percentile),
        ];
    }

    /**
     * Return benchmark placeholder when insufficient data exists.
     */
    private function getInsufficientDataBenchmark(array $scanCounts): array
    {
        $remaining = max(0, self::MIN_SCANS_FOR_BENCHMARK - Arr::get($scanCounts, 'total', 0));

        return [
            'position' => 'insufficient_data',
            'percentile' => null,
            'avg_similar_score' => null,
            'score_difference' => null,
            'comparison' => $remaining > 0
                ? "Run {$remaining} more scan(s) to unlock benchmark comparisons."
                : 'Calculating benchmarks...',
            'scans_until_benchmark' => $remaining,
        ];
    }

    /**
     * Calculate benchmark against competitor content.
     */
    private function calculateCompetitorBenchmark(
        array $baseScore,
        $competitorContent,
        ?int $teamId,
        ?int $userId
    ): array {
        if ($competitorContent->isEmpty()) {
            return [
                'position' => 'no_competitors',
                'percentile' => null,
                'avg_competitor_score' => null,
                'comparison' => 'No competitor content found for comparison. Mark scans as competitors to enable benchmarking.',
            ];
        }

        $competitorScores = [];
        foreach ($competitorContent as $doc) {
            $score = $this->baseScorer->quickScore($doc->content, [
                'team_id' => $teamId,
                'user_id' => $userId,
            ]);
            $competitorScores[] = Arr::get($score, 'score', 0);
        }

        $avgCompetitorScore = array_sum($competitorScores) / count($competitorScores);
        $currentScore = Arr::get($baseScore, 'score', 0);
        $scoreDiff = $currentScore - $avgCompetitorScore;

        // Calculate percentile (how many competitors you beat)
        $belowCount = count(array_filter($competitorScores, fn ($s) => $s < $currentScore));
        $percentile = round(($belowCount / count($competitorScores)) * 100);

        $position = match (true) {
            $scoreDiff >= 15 => 'dominant',
            $scoreDiff >= 5 => 'ahead',
            $scoreDiff >= -5 => 'competitive',
            $scoreDiff >= -15 => 'behind',
            default => 'far_behind',
        };

        return [
            'position' => $position,
            'percentile' => $percentile,
            'avg_competitor_score' => round($avgCompetitorScore, 1),
            'score_difference' => round($scoreDiff, 1),
            'competitors_analyzed' => count($competitorScores),
            'comparison' => $this->getCompetitorBenchmarkDescription($position, $scoreDiff, $percentile),
        ];
    }

    /**
     * Get description for competitor benchmark position.
     */
    private function getCompetitorBenchmarkDescription(string $position, float $diff, int $percentile): string
    {
        return match ($position) {
            'dominant' => "Outstanding! You outperform {$percentile}% of competitors by ".abs($diff).' points.',
            'ahead' => "Strong position. You're ".abs($diff)." points ahead of your competitors' average.",
            'competitive' => "You're competitive with your rivals. Focus on key differentiators.",
            'behind' => "You're ".abs($diff).' points behind competitors. Review their strategies for insights.',
            'far_behind' => "Significant gap to close. You're ".abs($diff).' points behind the competition.',
            default => 'Unable to determine competitive position.',
        };
    }

    /**
     * Run all AI analysis calls in parallel for faster processing.
     *
     * Makes concurrent API calls for:
     * - RAG analysis (content quality assessment)
     * - AI suggestions (improvement recommendations)
     * - Citation readiness (LLM citation potential)
     */
    private function runParallelAIAnalysis(
        string $content,
        array $baseScore,
        ?int $teamId,
        ?int $userId
    ): array {
        $apiKey = config('rag.openai.api_key');
        if (empty($apiKey) || ! config('rag.geo.use_rag_analysis', true)) {
            return [
                'rag_analysis' => null,
                'ai_suggestions' => null,
                'citation_readiness' => null,
            ];
        }

        try {
            // Prepare content for prompts
            $truncatedContent = mb_substr(strip_tags($content), 0, 4000);
            $model = config('rag.openai.model', 'gpt-4o-mini');

            // Build improvement prompt data
            $pillars = Arr::get($baseScore, 'pillars', []);
            $lowestPillars = collect($pillars)
                ->map(fn ($p, $key) => ['name' => $key, 'percentage' => Arr::get($p, 'percentage', 0)])
                ->sortBy('percentage')
                ->take(3)
                ->pluck('name')
                ->join(', ');
            $overallScore = Arr::get($baseScore, 'percentage', 0);
            $scoreBreakdown = json_encode($pillars, JSON_PRETTY_PRINT);

            // Run all API calls in parallel
            $responses = Http::pool(fn ($pool) => [
                // RAG Analysis
                $pool->as('rag')->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a GEO (Generative Engine Optimization) expert analyzing content for AI search engine optimization.'],
                        ['role' => 'user', 'content' => $this->buildRagAnalysisPrompt($truncatedContent)],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1500,
                ]),

                // AI Suggestions
                $pool->as('suggestions')->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a GEO expert providing actionable content improvement suggestions.'],
                        ['role' => 'user', 'content' => $this->buildSuggestionsPrompt($truncatedContent, $overallScore, $lowestPillars, $scoreBreakdown)],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 1500,
                ]),

                // Citation Readiness
                $pool->as('citation')->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert at evaluating content for AI citation potential. Return JSON only.'],
                        ['role' => 'user', 'content' => $this->buildCitationReadinessPrompt($truncatedContent)],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 800,
                ]),
            ]);

            // Process responses
            $ragAnalysis = null;
            $aiSuggestions = null;
            $citationReadiness = null;

            // Parse RAG analysis
            if (isset($responses['rag']) && $responses['rag']->successful()) {
                $ragContent = Arr::get($responses['rag']->json(), 'choices.0.message.content', '');
                $ragAnalysis = ['analysis' => $this->parseJsonResponse($ragContent), 'raw_response' => $ragContent];
            }

            // Parse AI suggestions
            if (isset($responses['suggestions']) && $responses['suggestions']->successful()) {
                $suggestionsContent = Arr::get($responses['suggestions']->json(), 'choices.0.message.content', '');
                $aiSuggestions = $this->parseSuggestionsResponse($suggestionsContent);
            }

            // Parse Citation readiness
            if (isset($responses['citation']) && $responses['citation']->successful()) {
                $citationContent = Arr::get($responses['citation']->json(), 'choices.0.message.content', '');
                $citationReadiness = $this->parseCitationResponse($citationContent);
            }

            return [
                'rag_analysis' => $ragAnalysis,
                'ai_suggestions' => $aiSuggestions,
                'citation_readiness' => $citationReadiness,
            ];
        } catch (\Exception $e) {
            Log::warning('Parallel AI analysis failed', ['error' => $e->getMessage()]);

            return [
                'rag_analysis' => ['error' => $e->getMessage()],
                'ai_suggestions' => null,
                'citation_readiness' => null,
            ];
        }
    }

    /**
     * Build prompt for RAG analysis.
     */
    private function buildRagAnalysisPrompt(string $content): string
    {
        return <<<PROMPT
Analyze the following content for Generative Engine Optimization (GEO). Evaluate how well it would be understood and cited by AI systems.

<content>
{$content}
</content>

Provide analysis in JSON format:
{
    "clarity_score": 0-10,
    "structure_score": 0-10,
    "answerability_score": 0-10,
    "strengths": ["..."],
    "weaknesses": ["..."],
    "quotable_snippets": ["sentences that could be directly quoted by AI"],
    "overall_assessment": "brief summary"
}
PROMPT;
    }

    /**
     * Build prompt for AI suggestions.
     */
    private function buildSuggestionsPrompt(string $content, float $score, string $weakestAreas, string $scoreBreakdown): string
    {
        return <<<PROMPT
Analyze this content and provide specific, actionable improvements to help it rank better in AI search engines.

<content>
{$content}
</content>

CURRENT GEO ANALYSIS:
- Overall Score: {$score}%
- Weakest Areas: {$weakestAreas}

Provide 5-8 specific improvements in JSON format:
[
    {
        "category": "definitions|citations|structure|clarity|authority|examples",
        "priority": "critical|high|medium|low",
        "suggestion": "specific actionable improvement",
        "reasoning": "why this helps with AI citations",
        "example": "example implementation if helpful"
    }
]

Focus on the weakest areas first. Be specific and actionable.
PROMPT;
    }

    /**
     * Build prompt for citation readiness evaluation.
     */
    private function buildCitationReadinessPrompt(string $content): string
    {
        return <<<PROMPT
Evaluate this content for AI Citation Readiness. Score each factor from 0-100:

**Factors:**
1. **Quotability** - Clear, quotable statements? Definitive answers? Memorable phrases?
2. **Authority** - Demonstrates expertise? Credentials, data sources, authoritative voice?
3. **Uniqueness** - Original insights, unique data, perspectives not found elsewhere?
4. **Structure** - Well-organized, easy to extract? Clear headings, lists, definitions?
5. **Factual Density** - Rich in verifiable facts, statistics, specific data points?

<content>
{$content}
</content>

Return JSON only:
{
  "score": <overall 0-100>,
  "factors": {
    "quotability": {"score": <0-100>, "reason": "<brief>"},
    "authority": {"score": <0-100>, "reason": "<brief>"},
    "uniqueness": {"score": <0-100>, "reason": "<brief>"},
    "structure": {"score": <0-100>, "reason": "<brief>"},
    "factual_density": {"score": <0-100>, "reason": "<brief>"}
  },
  "summary": "<1-2 sentence summary>"
}
PROMPT;
    }

    /**
     * Parse JSON from API response, handling markdown code blocks.
     */
    private function parseJsonResponse(string $response): ?array
    {
        $response = trim($response);
        if (str_starts_with($response, '```json')) {
            $response = substr($response, 7);
        }
        if (str_starts_with($response, '```')) {
            $response = substr($response, 3);
        }
        if (str_ends_with($response, '```')) {
            $response = substr($response, 0, -3);
        }

        $parsed = json_decode(trim($response), true);

        return json_last_error() === JSON_ERROR_NONE ? $parsed : null;
    }

    /**
     * Parse suggestions response into structured array.
     */
    private function parseSuggestionsResponse(string $response): array
    {
        $parsed = $this->parseJsonResponse($response);

        if (! is_array($parsed)) {
            return [];
        }

        // Handle both array of suggestions and object with suggestions key
        $suggestions = isset($parsed[0]) ? $parsed : (Arr::get($parsed, 'suggestions', []));

        return array_map(fn ($s) => [
            'category' => Arr::get($s, 'category', 'general'),
            'priority' => Arr::get($s, 'priority', 'medium'),
            'suggestion' => Arr::get($s, 'suggestion', ''),
            'reasoning' => Arr::get($s, 'reasoning', ''),
            'example' => Arr::get($s, 'example'),
        ], array_filter($suggestions, fn ($s) => ! empty(Arr::get($s, 'suggestion'))));
    }

    /**
     * Parse citation readiness response.
     */
    private function parseCitationResponse(string $response): ?array
    {
        $parsed = $this->parseJsonResponse($response);

        if (! is_array($parsed) || ! isset($parsed['score'])) {
            return null;
        }

        return $parsed;
    }

    /**
     * Calculate grade based on final score.
     */
    private function calculateGrade(float $score, float $maxScore): string
    {
        $percentage = ($score / $maxScore) * 100;

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
     * Perform quick analysis without LLM calls.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation (fallback when no team)
     */
    public function quickAnalyze(string $content, ?int $teamId, ?int $userId = null): array
    {
        // Extract main content for better embedding quality
        $contentForEmbedding = $content;
        if ($this->contentExtractionEnabled) {
            $contentForEmbedding = $this->contentExtractor->extract($content);
        }

        // Generate embedding with isolation for cache
        $isolationId = $this->getIsolationId($teamId, $userId);
        $embedding = $this->embeddingService->embed($contentForEmbedding, true, $isolationId);

        $baseScore = $this->baseScorer->quickScore($content, [
            'team_id' => $teamId,
            'user_id' => $userId,
            'embedding' => $embedding,
        ]);

        // Quick similarity check with lowered threshold
        $topSimilar = $this->vectorStore->searchByVector(
            $embedding,
            $teamId,
            $userId,
            limit: 3,
            threshold: config('rag.search.default_threshold', 0.35)
        );

        return array_merge($baseScore, [
            'similar_count' => $topSimilar->count(),
            'avg_similarity' => $topSimilar->avg('similarity') ?? 0,
        ]);
    }

    /**
     * Compare content against competitors.
     *
     * Only compares against documents belonging to the same isolation scope
     * to prevent cross-team/user data leakage.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation (fallback when no team)
     */
    public function compareWithCompetitors(
        string $content,
        ?int $teamId,
        ?int $userId = null,
        array $competitorUrls = []
    ): array {
        $myScore = $this->analyze($content, $teamId, $userId);

        $comparisons = [];

        // Compare with similar content in database
        // Security: Verify each document belongs to the same isolation scope
        foreach (Arr::get($myScore, 'similar_content', []) as $similar) {
            // Explicitly query with isolation constraint to prevent data leakage
            $query = Document::where('id', Arr::get($similar, 'id'));
            if ($teamId !== null) {
                $query->where('team_id', $teamId);
            } else {
                $query->where('user_id', $userId);
            }
            $doc = $query->first();

            if ($doc) {
                $competitorScore = $this->baseScorer->quickScore($doc->content, [
                    'team_id' => $teamId,
                    'user_id' => $userId,
                ]);

                // Only include title, not content details, to minimize exposure
                $comparisons[] = [
                    'title' => Arr::get($similar, 'title'),
                    'similarity' => Arr::get($similar, 'similarity'),
                    'their_score' => Arr::get($competitorScore, 'score'),
                    'score_difference' => Arr::get($myScore, 'score', 0) - Arr::get($competitorScore, 'score', 0),
                    'pillar_comparison' => $this->comparePillars(
                        Arr::get($myScore, 'pillars', []),
                        Arr::get($competitorScore, 'pillars', [])
                    ),
                ];
            }
        }

        return [
            'my_score' => Arr::get($myScore, 'score'),
            'my_grade' => Arr::get($myScore, 'grade'),
            'comparisons' => $comparisons,
            'position' => $this->calculatePosition(Arr::get($myScore, 'score', 0), $comparisons),
            'opportunities' => $this->identifyOpportunities($myScore, $comparisons),
        ];
    }

    /**
     * Track score changes over time.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation (fallback when no team)
     */
    public function trackProgress(
        string $contentId,
        string $content,
        ?int $teamId,
        ?int $userId = null
    ): array {
        $currentScore = $this->analyze($content, $teamId, $userId);

        // Build query with isolation scope
        $query = Document::query();
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId);
        }

        // Get historical scores from metadata
        // Using whereRaw for PostgreSQL JSONB ->> operator to query nested metadata fields
        $historical = $query
            ->whereRaw("metadata->>'content_id' = ?", [$contentId])
            ->whereRaw("metadata->>'type' = 'geo_score_history'")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $trend = 'stable';
        $change = 0;

        if ($historical->isNotEmpty()) {
            $previousScore = Arr::get($historical->first()->metadata, 'score', 0);
            $change = Arr::get($currentScore, 'score', 0) - $previousScore;

            if ($change > 2) {
                $trend = 'improving';
            } elseif ($change < -2) {
                $trend = 'declining';
            }
        }

        // Store current score for tracking
        Document::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'title' => "GEO Score: {$contentId}",
            'content' => json_encode(Arr::get($currentScore, 'pillars', [])),
            'metadata' => [
                'type' => 'geo_score_history',
                'content_id' => $contentId,
                'score' => Arr::get($currentScore, 'score'),
                'grade' => Arr::get($currentScore, 'grade'),
                'recorded_at' => now()->toISOString(),
            ],
        ]);

        return [
            'current' => $currentScore,
            'historical' => $historical->map(fn ($h) => [
                'score' => Arr::get($h->metadata, 'score', 0),
                'grade' => Arr::get($h->metadata, 'grade', 'N/A'),
                'recorded_at' => Arr::get($h->metadata, 'recorded_at', $h->created_at),
            ])->toArray(),
            'trend' => $trend,
            'change' => round($change, 1),
        ];
    }

    /**
     * Generate content optimized for GEO.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation (fallback when no team)
     */
    public function generateOptimizedContent(
        string $topic,
        ?int $teamId,
        ?int $userId = null,
        array $options = []
    ): array {
        // Get existing high-performing content for reference
        $references = $this->vectorStore->search(
            $topic,
            $teamId,
            $userId,
            limit: 5,
            threshold: config('rag.search.default_threshold', 0.35)
        );

        $prompt = $this->buildContentGenerationPrompt($topic, $references, $options);

        try {
            $response = $this->callLLM($prompt, 3000, 0.4);
            $generatedContent = Arr::get($response, 'content');

            // Score the generated content
            $generatedScore = $this->baseScorer->quickScore($generatedContent);

            return [
                'content' => $generatedContent,
                'estimated_score' => Arr::get($generatedScore, 'score'),
                'estimated_grade' => Arr::get($generatedScore, 'grade'),
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
    private function calculateBenchmark(
        array $baseScore,
        $similarContent,
        ?int $teamId,
        ?int $userId = null
    ): array {
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
            $score = $this->baseScorer->quickScore($doc->content, [
                'team_id' => $teamId,
                'user_id' => $userId,
            ]);
            $similarScores[] = Arr::get($score, 'score', 0);
        }

        $avgSimilarScore = array_sum($similarScores) / count($similarScores);
        $currentScore = Arr::get($baseScore, 'score', 0);
        $scoreDiff = $currentScore - $avgSimilarScore;

        // Calculate percentile
        $belowCount = count(array_filter($similarScores, fn ($s) => $s < $currentScore));
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
            'leader' => 'Excellent! Your content scores '.abs($diff).' points above similar content.',
            'above_average' => "Good performance. You're ".abs($diff).' points above the average.',
            'average' => 'Your content performs similarly to comparable content in your knowledge base.',
            'below_average' => "There's room for improvement. You're ".abs($diff).' points below average.',
            'needs_improvement' => "Significant optimization needed. You're ".abs($diff).' points below similar content.',
            default => 'Unable to determine benchmark position.',
        };
    }

    private function comparePillars(array $myPillars, array $theirPillars): array
    {
        $comparison = [];

        foreach ($myPillars as $key => $myPillar) {
            $theirPillar = Arr::get($theirPillars, $key);

            if ($theirPillar) {
                $myScore = Arr::get($myPillar, 'score', 0);
                $theirScore = Arr::get($theirPillar, 'score', 0);
                $comparison[$key] = [
                    'my_score' => $myScore,
                    'their_score' => $theirScore,
                    'difference' => $myScore - $theirScore,
                    'winner' => $myScore > $theirScore ? 'me' : ($myScore < $theirScore ? 'them' : 'tie'),
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
            foreach (Arr::get($comp, 'pillar_comparison', []) as $pillar => $data) {
                $winner = Arr::get($data, 'winner');
                $difference = Arr::get($data, 'difference', 0);
                if ($winner === 'them' && $difference < -2) {
                    $opportunities[] = [
                        'pillar' => $pillar,
                        'gap' => abs($difference),
                        'competitor' => Arr::get($comp, 'title'),
                        'suggestion' => "Improve {$pillar} to match competitor's level",
                    ];
                }
            }
        }

        // Sort by gap size
        usort($opportunities, fn ($a, $b) => Arr::get($b, 'gap', 0) <=> Arr::get($a, 'gap', 0));

        return array_slice($opportunities, 0, 5);
    }

    private function buildContentGenerationPrompt(string $topic, $references, array $options): string
    {
        $style = Arr::get($options, 'style', 'informative');
        $length = Arr::get($options, 'length', 'medium');
        $audience = Arr::get($options, 'audience', 'general');

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
        $response = Http::withToken(config('rag.openai.api_key'))
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
            Log::error('LLM API  error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('LLM API request failed. Please try again later.');
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'choices.0.message.content'),
            'model' => Arr::get($data, 'model'),
            'usage' => Arr::get($data, 'usage', []),
        ];
    }
}
