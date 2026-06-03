<?php

namespace App\Services\GEO;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Given a scanned page, infers the queries the page is positioned to compete
 * for and classifies each query as brand-named or concept-only.
 *
 * Built off the v4/v5 finding that whether a query names the brand is the
 * single biggest lever for AI citation rate. This service lets users see
 * which queries their page is realistically going to surface for, before
 * they spend tokens tracking citations they were never going to get.
 *
 * Cheap to run: one Haiku call per scan. Output is stored alongside the
 * scan results so the scan UI can render it without further API calls.
 */
class QueryIntentAnalyzer
{
    public const TYPE_BRAND_NAMED = 'brand_named';
    public const TYPE_CONCEPT = 'concept';

    public const LIKELIHOOD_HIGH = 'high';
    public const LIKELIHOOD_MEDIUM = 'medium';
    public const LIKELIHOOD_LOW = 'low';

    /**
     * @return array{queries: array<int, array{text: string, type: string, predicted_likelihood: string, rationale: string}>, primary_brand: ?string, summary: string}
     */
    public function analyze(string $content, ?string $url = null, ?string $brand = null): array
    {
        $apiKey = config('citations.claude.api_key');
        if (empty($apiKey)) {
            return $this->emptyResult('claude_api_key_not_configured');
        }

        $strippedContent = trim(strip_tags($content));
        if ($strippedContent === '') {
            return $this->emptyResult('empty_content');
        }

        $prompt = $this->buildPrompt($strippedContent, $url, $brand);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 1024,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Query intent analyzer non-200', ['status' => $response->status(), 'url' => $url]);
                return $this->emptyResult('api_error');
            }

            $data = $response->json();
            $text = Arr::get($data, 'content.0.text', '');
            $parsed = $this->extractJson($text);
            if (! $parsed) {
                return $this->emptyResult('parse_failure');
            }

            $queries = collect(Arr::get($parsed, 'queries', []))
                ->filter(fn ($q) => is_array($q) && isset($q['text']))
                ->map(fn ($q) => [
                    'text' => trim((string) $q['text']),
                    'type' => $this->normalizeType(Arr::get($q, 'type', self::TYPE_CONCEPT)),
                    'predicted_likelihood' => $this->normalizeLikelihood(Arr::get($q, 'predicted_likelihood', self::LIKELIHOOD_MEDIUM)),
                    'rationale' => trim((string) Arr::get($q, 'rationale', '')),
                ])
                ->filter(fn ($q) => $q['text'] !== '')
                ->values()
                ->take(8)
                ->toArray();

            return [
                'queries' => $queries,
                'primary_brand' => $brand ?: Arr::get($parsed, 'primary_brand'),
                'summary' => trim((string) Arr::get($parsed, 'summary', '')),
            ];

        } catch (\Throwable $e) {
            Log::error('Query intent analyzer failed', ['error' => $e->getMessage(), 'url' => $url]);
            return $this->emptyResult('exception');
        }
    }

    private function emptyResult(string $reason): array
    {
        return [
            'queries' => [],
            'primary_brand' => null,
            'summary' => "unable to analyze: {$reason}",
        ];
    }

    private function normalizeType(string $type): string
    {
        return in_array($type, [self::TYPE_BRAND_NAMED, self::TYPE_CONCEPT], true)
            ? $type
            : self::TYPE_CONCEPT;
    }

    private function normalizeLikelihood(string $likelihood): string
    {
        return in_array($likelihood, [self::LIKELIHOOD_HIGH, self::LIKELIHOOD_MEDIUM, self::LIKELIHOOD_LOW], true)
            ? $likelihood
            : self::LIKELIHOOD_MEDIUM;
    }

    private function buildPrompt(string $content, ?string $url, ?string $brand): string
    {
        $truncated = mb_strlen($content) > 4000
            ? mb_substr($content, 0, 4000)."\n\n[content truncated]"
            : $content;

        $urlLine = $url ? "URL: {$url}\n" : '';
        $brandLine = $brand ? "Brand: \"{$brand}\"\n" : "Brand: (extract from content)\n";

        return <<<PROMPT
You are analyzing a webpage to identify the questions a real user might ask an AI assistant where this page is positioned to be cited or recommended in the answer.

{$urlLine}{$brandLine}
Page content:
---
{$truncated}
---

Return a single JSON object (no markdown fences, no commentary) with these fields:

{
  "primary_brand": "the brand name this page belongs to, or null if it's not clearly a brand page",
  "summary": "one short sentence (max 200 chars) describing what kind of queries this page can plausibly compete for",
  "queries": [
    {
      "text": "the natural-language query a user might ask an AI assistant",
      "type": "brand_named" | "concept",
        // brand_named = the query explicitly names the brand (e.g. "is Casper a good mattress")
        // concept    = the query asks about a category/concept without naming the brand
      "predicted_likelihood": "high" | "medium" | "low",
        // high   = this page is the natural top answer for this query
        // medium = this page is a plausible answer among competitors
        // low    = this page might be tangentially relevant but probably won't be cited
      "rationale": "one sentence (max 150 chars) explaining the prediction"
    }
  ]
}

Guidelines:
- Generate 5 to 8 distinct queries — a mix of brand_named and concept queries when both make sense.
- Be realistic about likelihood. A heavily-SEO-optimized DTC landing page typically gets LOW likelihood for concept queries (AI answers without sourcing) but HIGH likelihood for brand_named queries.
- An educational/informational page gets HIGHER likelihood for concept queries.
- An obvious-fit category page (Mayo Clinic on diabetes) gets HIGH for concept queries; an off-category page (Allbirds for "running shoes" when Allbirds has pivoted away from running) gets LOW.
- Queries should sound like a real person typing into ChatGPT or Perplexity, not like SEO keyword strings.
- Return only the JSON object.
PROMPT;
    }

    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }
}
