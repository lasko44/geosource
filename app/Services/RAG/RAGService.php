<?php

namespace App\Services\RAG;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Retrieval-Augmented Generation (RAG) Service
 *
 * Combines vector search with LLM generation for:
 * - Intelligent content analysis
 * - Question answering
 * - Content recommendations
 * - GEO optimization suggestions
 */
class RAGService
{
    public function __construct(
        private VectorStore $vectorStore,
        private EmbeddingService $embeddingService,
    ) {}

    /**
     * Retrieve relevant context for a query.
     */
    public function retrieve(
        string $query,
        int $teamId,
        int $limit = 5,
        float $threshold = 0.5,
        array $filters = []
    ): Collection {
        return $this->vectorStore->search($query, $teamId, $limit, $threshold, $filters);
    }

    /**
     * Retrieve and augment with context, returning formatted prompt context.
     */
    public function retrieveContext(
        string $query,
        int $teamId,
        int $limit = 5,
        array $filters = []
    ): array {
        $results = $this->vectorStore->hybridSearch($query, $teamId, $limit, 0.7, $filters);

        $context = $results->map(function ($doc) {
            return [
                'title' => $doc->title,
                'content' => $this->truncateContent($doc->content, 1500),
                'similarity' => round($doc->similarity, 4),
                'metadata' => $doc->metadata,
            ];
        })->toArray();

        return [
            'query' => $query,
            'context' => $context,
            'total_retrieved' => count($context),
            'formatted_context' => $this->formatContextForPrompt($context),
        ];
    }

    /**
     * Generate an answer using RAG.
     */
    public function generate(
        string $query,
        int $teamId,
        array $options = []
    ): array {
        $contextLimit = $options['context_limit'] ?? 5;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.3;
        $systemPrompt = $options['system_prompt'] ?? null;
        $filters = $options['filters'] ?? [];

        // Retrieve relevant context
        $contextData = $this->retrieveContext($query, $teamId, $contextLimit, $filters);

        // Build the prompt
        $prompt = $this->buildPrompt($query, $contextData['formatted_context'], $systemPrompt);

        // Generate response
        $response = $this->callLLM($prompt, $maxTokens, $temperature);

        return [
            'query' => $query,
            'answer' => $response['content'],
            'context_used' => $contextData['context'],
            'model' => $response['model'],
            'usage' => $response['usage'],
        ];
    }

    /**
     * Analyze content for GEO optimization using RAG.
     */
    public function analyzeForGEO(
        string $content,
        int $teamId,
        array $options = []
    ): array {
        // Find similar high-performing content
        $similarContent = $this->vectorStore->search(
            $content,
            $teamId,
            limit: $options['comparison_limit'] ?? 5,
            threshold: 0.4
        );

        // Build analysis prompt
        $analysisPrompt = $this->buildGEOAnalysisPrompt($content, $similarContent);

        // Generate analysis
        $response = $this->callLLM($analysisPrompt, 2000, 0.2);

        // Parse structured response
        $analysis = $this->parseGEOAnalysis($response['content']);

        return [
            'analysis' => $analysis,
            'similar_content' => $similarContent->map(fn ($doc) => [
                'title' => $doc->title,
                'similarity' => round($doc->similarity, 4),
            ])->toArray(),
            'raw_response' => $response['content'],
        ];
    }

    /**
     * Generate content improvement suggestions.
     */
    public function suggestImprovements(
        string $content,
        array $geoScore,
        int $teamId
    ): array {
        // Get similar well-optimized content for reference
        $referenceContent = $this->vectorStore->search(
            $content,
            $teamId,
            limit: 3,
            threshold: 0.5,
            filters: ['geo_score_min' => 70] // Only high-scoring content
        );

        $prompt = $this->buildImprovementPrompt($content, $geoScore, $referenceContent);

        $response = $this->callLLM($prompt, 1500, 0.3);

        return [
            'suggestions' => $this->parseSuggestions($response['content']),
            'raw_response' => $response['content'],
        ];
    }

    /**
     * Answer a question based on indexed content.
     */
    public function answerQuestion(
        string $question,
        int $teamId,
        array $options = []
    ): array {
        $systemPrompt = "You are a helpful assistant that answers questions based on the provided context.
        If the context doesn't contain enough information to answer the question fully, say so.
        Always cite which source you're using when possible.
        Be concise and direct in your answers.";

        return $this->generate($question, $teamId, array_merge($options, [
            'system_prompt' => $systemPrompt,
        ]));
    }

    /**
     * Summarize a topic from multiple documents.
     */
    public function summarizeTopic(
        string $topic,
        int $teamId,
        int $documentLimit = 10
    ): array {
        $documents = $this->vectorStore->search($topic, $teamId, $documentLimit, 0.4);

        if ($documents->isEmpty()) {
            return [
                'summary' => 'No relevant documents found for this topic.',
                'documents_used' => 0,
            ];
        }

        $contextParts = $documents->map(fn ($doc) => "## {$doc->title}\n{$this->truncateContent($doc->content, 800)}")->join("\n\n---\n\n");

        $prompt = "Based on the following documents about \"{$topic}\", provide a comprehensive summary that synthesizes the key information:\n\n{$contextParts}\n\n---\n\nProvide a well-structured summary covering the main points, key insights, and any notable patterns or themes across these documents.";

        $response = $this->callLLM($prompt, 1500, 0.3);

        return [
            'summary' => $response['content'],
            'documents_used' => $documents->count(),
            'sources' => $documents->pluck('title')->toArray(),
        ];
    }

    /**
     * Find content gaps based on a topic.
     */
    public function findContentGaps(
        string $topic,
        int $teamId
    ): array {
        // Search for existing content
        $existingContent = $this->vectorStore->search($topic, $teamId, 20, 0.3);

        // Generate subtopics/questions that should be covered
        $prompt = "Given the topic \"{$topic}\" and the following existing content titles:\n\n";
        $prompt .= $existingContent->pluck('title')->map(fn ($t, $i) => ($i + 1).". {$t}")->join("\n");
        $prompt .= "\n\nIdentify 5-10 content gaps or subtopics that are NOT covered but should be for comprehensive topic coverage. For each gap:\n1. Name the missing subtopic\n2. Explain why it's important\n3. Suggest a title for new content\n\nFormat as JSON array with keys: subtopic, importance, suggested_title";

        $response = $this->callLLM($prompt, 1500, 0.4);

        $gaps = $this->parseJsonFromResponse($response['content']);

        return [
            'topic' => $topic,
            'existing_content_count' => $existingContent->count(),
            'gaps' => $gaps,
        ];
    }

    /**
     * Call the LLM API.
     */
    private function callLLM(string $prompt, int $maxTokens = 1000, float $temperature = 0.3): array
    {
        $provider = config('rag.llm.provider', 'openai');

        return match ($provider) {
            'openai' => $this->callOpenAI($prompt, $maxTokens, $temperature),
            'anthropic' => $this->callAnthropic($prompt, $maxTokens, $temperature),
            default => throw new \RuntimeException("Unknown LLM provider: {$provider}"),
        };
    }

    private function callOpenAI(string $prompt, int $maxTokens, float $temperature): array
    {
        $response = Http::withToken(config('rag.openai.api_key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('rag.llm.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: '.$response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'],
            'usage' => $data['usage'],
        ];
    }

    private function callAnthropic(string $prompt, int $maxTokens, float $temperature): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('rag.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('rag.llm.model', 'claude-3-haiku-20240307'),
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic API error: '.$response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'],
            'model' => $data['model'],
            'usage' => [
                'input_tokens' => $data['usage']['input_tokens'],
                'output_tokens' => $data['usage']['output_tokens'],
            ],
        ];
    }

    private function buildPrompt(string $query, string $context, ?string $systemPrompt = null): string
    {
        $system = $systemPrompt ?? 'You are a helpful assistant. Answer questions based on the provided context. Be accurate and concise.';

        return "{$system}\n\n---\n\nContext:\n{$context}\n\n---\n\nQuestion: {$query}\n\nAnswer:";
    }

    private function buildGEOAnalysisPrompt(string $content, Collection $similarContent): string
    {
        $truncatedContent = $this->truncateContent($content, 3000);
        $comparisons = $similarContent->take(3)->map(fn ($doc) => "Title: {$doc->title}\nSimilarity: ".round($doc->similarity, 2)."\nExcerpt: ".$this->truncateContent($doc->content, 500))->join("\n\n---\n\n");

        return <<<PROMPT
Analyze the following content for Generative Engine Optimization (GEO). Evaluate how well it would be understood and cited by AI systems like ChatGPT, Claude, and Perplexity.

CONTENT TO ANALYZE:
{$truncatedContent}

SIMILAR CONTENT FOR COMPARISON:
{$comparisons}

Provide analysis in the following JSON format:
{
    "clarity_score": 0-10,
    "structure_score": 0-10,
    "answerability_score": 0-10,
    "strengths": ["..."],
    "weaknesses": ["..."],
    "specific_improvements": [
        {"area": "...", "current": "...", "suggested": "...", "reason": "..."}
    ],
    "quotable_snippets": ["sentences that could be directly quoted by AI"],
    "missing_elements": ["things that should be added"],
    "overall_assessment": "brief summary"
}
PROMPT;
    }

    private function buildImprovementPrompt(string $content, array $geoScore, Collection $referenceContent): string
    {
        $truncatedContent = $this->truncateContent($content, 2500);
        $scoreBreakdown = json_encode($geoScore['pillars'] ?? [], JSON_PRETTY_PRINT);

        $references = $referenceContent->take(2)->map(fn ($doc) => "### {$doc->title}\n".$this->truncateContent($doc->content, 600))->join("\n\n");

        return <<<PROMPT
Based on the GEO score analysis, suggest specific improvements for this content.

CONTENT:
{$truncatedContent}

CURRENT GEO SCORES:
{$scoreBreakdown}

REFERENCE CONTENT (well-optimized examples):
{$references}

Provide 5-10 specific, actionable improvements. For each:
1. Identify the exact location/sentence to improve
2. Provide the improved version
3. Explain why this helps GEO

Format as JSON:
{
    "improvements": [
        {
            "priority": "high|medium|low",
            "category": "definitions|structure|authority|machine_readable|answerability",
            "original": "current text or description",
            "improved": "suggested improvement",
            "explanation": "why this helps"
        }
    ]
}
PROMPT;
    }

    private function formatContextForPrompt(array $context): string
    {
        if (empty($context)) {
            return 'No relevant context found.';
        }

        return collect($context)
            ->map(fn ($doc, $i) => '### Source '.($i + 1).": {$doc['title']}\n{$doc['content']}")
            ->join("\n\n---\n\n");
    }

    private function truncateContent(string $content, int $maxLength): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        // Try to truncate at a sentence boundary
        $truncated = substr($content, 0, $maxLength);
        $lastPeriod = strrpos($truncated, '.');

        if ($lastPeriod !== false && $lastPeriod > $maxLength * 0.7) {
            return substr($truncated, 0, $lastPeriod + 1);
        }

        return $truncated.'...';
    }

    private function parseGEOAnalysis(string $response): array
    {
        $json = $this->parseJsonFromResponse($response);

        return $json ?: [
            'raw_analysis' => $response,
            'parse_error' => 'Could not parse structured response',
        ];
    }

    private function parseSuggestions(string $response): array
    {
        $json = $this->parseJsonFromResponse($response);

        return $json['improvements'] ?? $json ?: [];
    }

    private function parseJsonFromResponse(string $response): ?array
    {
        // Try to extract JSON from response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Try array format
        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }
}
