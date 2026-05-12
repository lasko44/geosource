<?php

namespace App\Jobs;

use App\Models\CitationResearch;
use App\Models\Scan;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Performs citation research across multiple AI platforms.
 */
class RunCitationResearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    private const STEPS = [
        'initializing' => ['label' => 'Initializing', 'percent' => 5],
        'querying_perplexity' => ['label' => 'Querying Perplexity', 'percent' => 15],
        'querying_chatgpt' => ['label' => 'Querying ChatGPT', 'percent' => 25],
        'querying_claude' => ['label' => 'Querying Claude', 'percent' => 35],
        'querying_gemini' => ['label' => 'Querying Gemini', 'percent' => 45],
        'querying_deepseek' => ['label' => 'Querying DeepSeek', 'percent' => 55],
        'extracting_sources' => ['label' => 'Extracting sources', 'percent' => 65],
        'analyzing_patterns' => ['label' => 'Analyzing patterns', 'percent' => 75],
        'scanning_sources' => ['label' => 'Scanning top sources', 'percent' => 85],
        'generating_report' => ['label' => 'Generating report', 'percent' => 95],
        'completed' => ['label' => 'Completed', 'percent' => 100],
    ];

    public function __construct(
        public CitationResearch $research
    ) {}

    public function handle(): void
    {
        $this->updateProgress('initializing');

        try {
            $platformResponses = [];
            $platformsQueried = [];

            // Query each AI platform
            foreach (CitationResearch::PLATFORMS as $platform) {
                $step = "querying_{$platform}";
                if ($platform === 'openai') {
                    $step = 'querying_chatgpt';
                }
                $this->updateProgress($step);

                try {
                    $response = $this->queryPlatform($platform, $this->research->topic);
                    $platformResponses[$platform] = $response;
                    $platformsQueried[] = $platform;
                } catch (\Exception $e) {
                    Log::warning("Research platform query failed: {$platform}", [
                        'research_id' => $this->research->id,
                        'error' => $e->getMessage(),
                    ]);
                    $platformResponses[$platform] = [
                        'error' => $e->getMessage(),
                        'content' => null,
                        'citations' => [],
                    ];
                }
            }

            // Update with platform responses
            $this->research->update([
                'platforms_queried' => $platformsQueried,
                'platform_responses' => $platformResponses,
            ]);

            // Extract and deduplicate sources
            $this->updateProgress('extracting_sources');
            $sources = $this->extractAndRankSources($platformResponses);

            $this->research->update([
                'sources_found' => $sources,
            ]);

            // Analyze patterns
            $this->updateProgress('analyzing_patterns');
            $analysis = $this->analyzePatterns($this->research->topic, $platformResponses, $sources);

            $this->research->update([
                'analysis_results' => $analysis,
            ]);

            // For audit tier, scan top sources
            if ($this->research->isAuditTier()) {
                $this->updateProgress('scanning_sources');
                $this->scanTopSources($sources);
            }

            // Generate report
            $this->updateProgress('generating_report');
            $report = $this->generateReport($this->research->topic, $platformResponses, $sources, $analysis);

            // Mark as completed
            $this->research->update([
                'status' => CitationResearch::STATUS_COMPLETED,
                'report' => $report,
                'progress_step' => 'Completed',
                'progress_percent' => 100,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $this->markFailed($e->getMessage());
        }
    }

    /**
     * Query a specific AI platform for research.
     */
    private function queryPlatform(string $platform, string $topic): array
    {
        $prompt = $this->buildResearchPrompt($topic);

        return match ($platform) {
            'perplexity' => $this->queryPerplexity($prompt),
            'openai' => $this->queryOpenAI($prompt),
            'claude' => $this->queryClaude($prompt),
            'gemini' => $this->queryGemini($prompt),
            'deepseek' => $this->queryDeepSeek($prompt),
            default => throw new \RuntimeException("Unsupported platform: {$platform}"),
        };
    }

    /**
     * Build a research-focused prompt for AI platforms.
     */
    private function buildResearchPrompt(string $topic): string
    {
        return <<<PROMPT
Research the following topic and provide a comprehensive answer with citations to authoritative sources.

Topic: {$topic}

Instructions:
1. Provide a thorough, well-researched response
2. ALWAYS cite sources using full URLs when possible
3. Include at least 3-5 relevant sources
4. Focus on authoritative, trustworthy sources
5. Explain why each source is credible or relevant

Please format your response with clear sections and numbered source references.
PROMPT;
    }

    /**
     * Query Perplexity API.
     */
    private function queryPerplexity(string $prompt): array
    {
        $apiKey = config('citations.perplexity.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('Perplexity API key not configured');
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(config('citations.perplexity.base_url').'/chat/completions', [
                'model' => config('citations.perplexity.model', 'llama-3.1-sonar-large-128k-online'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'web_search_options' => [
                    'search_context_size' => 'high',
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Perplexity API error: '.$response->status());
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'choices.0.message.content'),
            'citations' => Arr::get($data, 'citations', []),
            'model' => Arr::get($data, 'model'),
        ];
    }

    /**
     * Query OpenAI API with Tavily search.
     */
    private function queryOpenAI(string $prompt): array
    {
        $openaiKey = config('citations.openai.api_key');
        $tavilyKey = config('citations.tavily.api_key');

        if (! $openaiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        // First search with Tavily if available
        $searchContext = '';
        $citations = [];

        if ($tavilyKey) {
            try {
                $searchResponse = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$tavilyKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.tavily.com/search', [
                        'query' => $this->research->topic,
                        'search_depth' => 'advanced',
                        'max_results' => 10,
                    ]);

                if ($searchResponse->successful()) {
                    $results = Arr::get($searchResponse->json(), 'results', []);
                    foreach ($results as $i => $result) {
                        $num = $i + 1;
                        $searchContext .= "[{$num}] {$result['title']}\nURL: {$result['url']}\n{$result['content']}\n\n";
                        $citations[] = $result['url'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Tavily search failed for research', ['error' => $e->getMessage()]);
            }
        }

        $enhancedPrompt = $searchContext
            ? "Using these search results:\n\n{$searchContext}\n\n{$prompt}"
            : $prompt;

        $response = Http::timeout(90)
            ->withHeaders([
                'Authorization' => 'Bearer '.$openaiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('citations.openai.model', 'gpt-4o'),
                'messages' => [
                    ['role' => 'user', 'content' => $enhancedPrompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 2048,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: '.$response->status());
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'choices.0.message.content'),
            'citations' => $citations,
            'model' => Arr::get($data, 'model'),
        ];
    }

    /**
     * Query Claude API.
     */
    private function queryClaude(string $prompt): array
    {
        $apiKey = config('citations.anthropic.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $response = Http::timeout(90)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('citations.anthropic.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude API error: '.$response->status());
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'content.0.text'),
            'citations' => [],
            'model' => Arr::get($data, 'model'),
        ];
    }

    /**
     * Query Gemini API.
     */
    private function queryGemini(string $prompt): array
    {
        $apiKey = config('citations.gemini.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('Gemini API key not configured');
        }

        $model = config('citations.gemini.model', 'gemini-2.0-flash');

        $response = Http::timeout(90)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 2048,
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API error: '.$response->status());
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'candidates.0.content.parts.0.text'),
            'citations' => [],
            'model' => $model,
        ];
    }

    /**
     * Query DeepSeek API.
     */
    private function queryDeepSeek(string $prompt): array
    {
        $apiKey = config('citations.deepseek.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('DeepSeek API key not configured');
        }

        $response = Http::timeout(90)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(config('citations.deepseek.base_url', 'https://api.deepseek.com').'/chat/completions', [
                'model' => config('citations.deepseek.model', 'deepseek-chat'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 2048,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('DeepSeek API error: '.$response->status());
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'choices.0.message.content'),
            'citations' => [],
            'model' => Arr::get($data, 'model'),
        ];
    }

    /**
     * Extract and rank sources from all platform responses.
     */
    private function extractAndRankSources(array $platformResponses): array
    {
        $urlCounts = [];
        $urlDetails = [];

        foreach ($platformResponses as $platform => $response) {
            if (isset($response['error'])) {
                continue;
            }

            // Get explicit citations
            $citations = Arr::get($response, 'citations', []);
            foreach ($citations as $url) {
                if (! $this->isValidUrl($url)) {
                    continue;
                }
                $normalizedUrl = $this->normalizeUrl($url);
                $urlCounts[$normalizedUrl] = ($urlCounts[$normalizedUrl] ?? 0) + 1;
                $urlDetails[$normalizedUrl]['platforms'][] = $platform;
                $urlDetails[$normalizedUrl]['url'] = $url;
            }

            // Extract URLs from content
            $content = Arr::get($response, 'content', '');
            $extractedUrls = $this->extractUrlsFromContent($content);
            foreach ($extractedUrls as $url) {
                $normalizedUrl = $this->normalizeUrl($url);
                $urlCounts[$normalizedUrl] = ($urlCounts[$normalizedUrl] ?? 0) + 1;
                if (! isset($urlDetails[$normalizedUrl]['platforms']) || ! in_array($platform, $urlDetails[$normalizedUrl]['platforms'])) {
                    $urlDetails[$normalizedUrl]['platforms'][] = $platform;
                }
                $urlDetails[$normalizedUrl]['url'] = $url;
            }
        }

        // Sort by citation count
        arsort($urlCounts);

        // Build ranked source list
        $sources = [];
        foreach ($urlCounts as $normalizedUrl => $count) {
            $sources[] = [
                'url' => $urlDetails[$normalizedUrl]['url'],
                'citation_count' => $count,
                'platforms' => array_unique($urlDetails[$normalizedUrl]['platforms']),
                'domain' => parse_url($urlDetails[$normalizedUrl]['url'], PHP_URL_HOST),
            ];
        }

        return array_slice($sources, 0, 20); // Top 20 sources
    }

    /**
     * Extract URLs from text content.
     */
    private function extractUrlsFromContent(string $content): array
    {
        $pattern = '/https?:\/\/[^\s<>\[\]()"\',]+/i';
        preg_match_all($pattern, $content, $matches);

        $urls = [];
        foreach ($matches[0] as $url) {
            // Clean up trailing punctuation
            $url = rtrim($url, '.,;:!?)\'">');
            if ($this->isValidUrl($url)) {
                $urls[] = $url;
            }
        }

        return array_unique($urls);
    }

    /**
     * Check if URL is valid for citation.
     */
    private function isValidUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        // Exclude common non-content URLs
        $excludedDomains = ['localhost', '127.0.0.1', 'example.com'];

        return ! in_array($host, $excludedDomains);
    }

    /**
     * Normalize URL for deduplication.
     */
    private function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = strtolower($parsed['host'] ?? '');
        $path = rtrim($parsed['path'] ?? '', '/');

        return "{$scheme}://{$host}{$path}";
    }

    /**
     * Analyze citation patterns using LLM.
     */
    private function analyzePatterns(string $topic, array $platformResponses, array $sources): array
    {
        $responseSummaries = [];
        foreach ($platformResponses as $platform => $response) {
            if (isset($response['error'])) {
                $responseSummaries[] = "- {$platform}: Failed ({$response['error']})";
            } else {
                $content = Arr::get($response, 'content', '');
                $preview = substr($content, 0, 500);
                $responseSummaries[] = "- {$platform}: {$preview}...";
            }
        }

        $topSources = array_slice($sources, 0, 10);
        $sourcesList = '';
        foreach ($topSources as $i => $source) {
            $num = $i + 1;
            $platforms = implode(', ', $source['platforms']);
            $sourcesList .= "{$num}. {$source['url']} (cited by: {$platforms}, count: {$source['citation_count']})\n";
        }

        $prompt = <<<PROMPT
Analyze the following AI citation research results for the topic: "{$topic}"

## Platform Responses:
{$this->formatPlatformResponses($platformResponses)}

## Top Cited Sources:
{$sourcesList}

Provide a structured analysis with:
1. **Content Patterns**: What types of content get cited most (blogs, docs, studies, news)?
2. **Source Authority**: What makes sources authoritative for this topic?
3. **Common Themes**: What themes or angles do multiple AIs agree on?
4. **Citation Gaps**: What perspectives or sources might be missing?
5. **Key Insights**: Most important findings about what gets cited

Format as JSON with keys: content_patterns, source_authority, common_themes, citation_gaps, key_insights
PROMPT;

        try {
            $result = $this->callLLM($prompt, 1500);
            $content = Arr::get($result, 'content', '');

            // Try to parse as JSON
            if (preg_match('/\{[\s\S]*\}/m', $content, $matches)) {
                $parsed = json_decode($matches[0], true);
                if ($parsed) {
                    return $parsed;
                }
            }

            // Fallback to raw content
            return [
                'raw_analysis' => $content,
                'content_patterns' => null,
                'source_authority' => null,
                'common_themes' => null,
                'citation_gaps' => null,
                'key_insights' => null,
            ];
        } catch (\Exception $e) {
            Log::warning('Research pattern analysis failed', [
                'research_id' => $this->research->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Analysis unavailable',
                'content_patterns' => null,
            ];
        }
    }

    /**
     * Format platform responses for analysis prompt.
     */
    private function formatPlatformResponses(array $platformResponses): string
    {
        $formatted = '';
        foreach ($platformResponses as $platform => $response) {
            $formatted .= "### {$platform}\n";
            if (isset($response['error'])) {
                $formatted .= "Error: {$response['error']}\n\n";
            } else {
                $content = Arr::get($response, 'content', '');
                // Limit to first 1000 chars for analysis
                $formatted .= substr($content, 0, 1000)."\n\n";
            }
        }

        return $formatted;
    }

    /**
     * Scan top sources using GEO scanner (audit tier only).
     */
    private function scanTopSources(array $sources): void
    {
        $topSources = array_slice($sources, 0, 3);

        foreach ($topSources as $source) {
            try {
                $scan = Scan::create([
                    'user_id' => $this->research->user_id,
                    'team_id' => $this->research->team_id,
                    'citation_research_id' => $this->research->id,
                    'url' => $source['url'],
                    'status' => 'pending',
                    'requested_tier' => 'basic', // Use basic tier for audit scans
                    'tokens_charged' => false, // Included in research price
                    'tokens_amount' => 0,
                ]);

                ScanWebsiteJob::dispatch($scan);

            } catch (\Exception $e) {
                Log::warning('Failed to create audit scan for research', [
                    'research_id' => $this->research->id,
                    'url' => $source['url'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate the final research report.
     */
    private function generateReport(string $topic, array $platformResponses, array $sources, array $analysis): string
    {
        $successfulPlatforms = count(array_filter($platformResponses, fn ($r) => ! isset($r['error'])));
        $topSources = array_slice($sources, 0, 10);

        $sourcesList = '';
        foreach ($topSources as $i => $source) {
            $num = $i + 1;
            $platforms = implode(', ', $source['platforms']);
            $sourcesList .= "{$num}. {$source['url']}\n   - Cited by: {$platforms}\n   - Citation count: {$source['citation_count']}\n";
        }

        $analysisText = '';
        if (! empty($analysis['content_patterns'])) {
            $analysisText .= "**Content Patterns:** ".json_encode($analysis['content_patterns'])."\n\n";
        }
        if (! empty($analysis['key_insights'])) {
            $analysisText .= "**Key Insights:** ".json_encode($analysis['key_insights'])."\n\n";
        }

        $prompt = <<<PROMPT
Generate a comprehensive citation research report for the topic: "{$topic}"

## Research Summary:
- Platforms queried: {$successfulPlatforms}/5 successful
- Total unique sources found: {$this->countUniqueSources($sources)}

## Top Cited Sources:
{$sourcesList}

## Pattern Analysis:
{$analysisText}

Write a professional report with:
1. **Executive Summary** (2-3 sentences)
2. **What Gets Cited** - Key patterns in what AI systems cite
3. **Top Sources Analysis** - Why these sources rank highly
4. **Actionable Recommendations** - How to improve citation likelihood
5. **Next Steps** - Specific actions to take

Write in a helpful, actionable tone. Focus on practical insights.
PROMPT;

        try {
            $result = $this->callLLM($prompt, 2000);

            return Arr::get($result, 'content', 'Report generation failed.');
        } catch (\Exception $e) {
            Log::warning('Research report generation failed', [
                'research_id' => $this->research->id,
                'error' => $e->getMessage(),
            ]);

            // Return a basic report
            return "# Citation Research Report\n\n"
                ."## Topic: {$topic}\n\n"
                ."## Sources Found\n{$sourcesList}\n\n"
                ."Report generation encountered an error. Please review the raw data above.";
        }
    }

    /**
     * Count unique sources.
     */
    private function countUniqueSources(array $sources): int
    {
        return count($sources);
    }

    /**
     * Call LLM for analysis/report generation.
     */
    private function callLLM(string $prompt, int $maxTokens = 1000): array
    {
        $response = Http::withToken(config('rag.openai.api_key'))
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('rag.llm.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            Log::error('LLM API error in research job', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('LLM API request failed.');
        }

        $data = $response->json();

        return [
            'content' => Arr::get($data, 'choices.0.message.content'),
            'model' => Arr::get($data, 'model'),
            'usage' => Arr::get($data, 'usage', []),
        ];
    }

    /**
     * Update progress status.
     */
    private function updateProgress(string $step): void
    {
        $stepData = self::STEPS[$step] ?? ['label' => $step, 'percent' => 0];

        $this->research->update([
            'status' => CitationResearch::STATUS_PROCESSING,
            'progress_step' => $stepData['label'],
            'progress_percent' => $stepData['percent'],
            'started_at' => $this->research->started_at ?? now(),
        ]);
    }

    /**
     * Mark research as failed and refund tokens.
     */
    private function markFailed(string $message): void
    {
        $this->research->update([
            'status' => CitationResearch::STATUS_FAILED,
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => $message,
            'completed_at' => now(),
        ]);

        $this->refundTokens();

        Log::error('Citation research failed', [
            'research_id' => $this->research->id,
            'error' => $message,
        ]);
    }

    /**
     * Refund tokens for failed research.
     */
    private function refundTokens(): void
    {
        if (! $this->research->tokens_charged || ! $this->research->tokens_amount || $this->research->tokens_refunded) {
            return;
        }

        $user = $this->research->user;
        if (! $user || $user->is_admin) {
            return;
        }

        try {
            $tokenService = app(TokenService::class);
            $amount = $this->research->tokens_amount;

            $tokenService->refund(
                $user,
                $amount,
                "Refund for failed {$this->research->tier} citation research"
            );

            $this->research->update([
                'tokens_refunded' => true,
            ]);

            Log::info('Tokens refunded for failed research', [
                'user_id' => $user->id,
                'research_id' => $this->research->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refund tokens for research', [
                'user_id' => $user->id,
                'research_id' => $this->research->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $message = $exception?->getMessage() ?? 'Job failed after maximum attempts';

        $this->research->update([
            'status' => CitationResearch::STATUS_FAILED,
            'progress_step' => 'Failed',
            'progress_percent' => 0,
            'error_message' => 'Research failed. Please try again.',
            'completed_at' => now(),
        ]);

        $this->refundTokens();

        Log::error('Citation research job failed', [
            'research_id' => $this->research->id,
            'error' => $message,
        ]);
    }
}
