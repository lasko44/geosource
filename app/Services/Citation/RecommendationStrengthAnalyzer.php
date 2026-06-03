<?php

namespace App\Services\Citation;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Classifies how strongly a brand was recommended within an AI response.
 *
 * The base citation check answers a yes/no question (was the brand mentioned).
 * This analyzer goes further and scores the *strength* of the recommendation
 * by asking Claude Haiku to read the response with structured output:
 * mention type, position in the answer, top-pick rank if any, and whether
 * the AI provided a buy-intent link. The composite strength_score (0-100)
 * is a proxy for "would this drive a sale" — closer to the original commenter's
 * point that citation rate isn't the same as business impact.
 *
 * Cheap to run: one Haiku call per (brand, ai_response). Reuses already-stored
 * response text, no new web search.
 */
class RecommendationStrengthAnalyzer
{
    public const MENTION_RECOMMENDED = 'recommended';
    public const MENTION_NEUTRAL = 'neutral';
    public const MENTION_NEGATIVE = 'negative';
    public const MENTION_ABSENT = 'absent';

    /**
     * @return array{
     *   mention_type: string,
     *   position_rank: int|null,
     *   is_top_pick: bool,
     *   top_pick_rank: int|null,
     *   has_buy_link: bool,
     *   reasoning: string,
     *   strength_score: float
     * }
     */
    public function analyze(string $aiResponse, string $domain, array $brandNames = []): array
    {
        $apiKey = config('citations.claude.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('Claude API key is not configured.');
        }

        if (trim($aiResponse) === '') {
            return $this->absentResult('empty response');
        }

        $brandList = empty($brandNames) ? [$domain] : array_merge([$domain], $brandNames);
        $prompt = $this->buildPrompt($aiResponse, $domain, $brandList);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 512,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Strength analyzer API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'domain' => $domain,
            ]);
            throw new RuntimeException('Strength analyzer Claude error: '.$response->status());
        }

        $data = $response->json();
        $text = Arr::get($data, 'content.0.text', '');
        $parsed = $this->extractJson($text);
        if ($parsed === null) {
            Log::warning('Strength analyzer returned unparseable JSON', ['text' => $text, 'domain' => $domain]);
            return $this->absentResult('parse_failure');
        }

        $mentionType = Arr::get($parsed, 'mention_type', self::MENTION_ABSENT);
        $positionRank = Arr::get($parsed, 'position_rank');
        $topPickRank = Arr::get($parsed, 'top_pick_rank');
        $hasBuyLink = (bool) Arr::get($parsed, 'has_buy_link', false);

        return [
            'mention_type' => $mentionType,
            'position_rank' => $positionRank !== null ? (int) $positionRank : null,
            'is_top_pick' => $topPickRank !== null && (int) $topPickRank <= 3,
            'top_pick_rank' => $topPickRank !== null ? (int) $topPickRank : null,
            'has_buy_link' => $hasBuyLink,
            'reasoning' => Arr::get($parsed, 'reasoning', ''),
            'strength_score' => $this->compositeScore($mentionType, $positionRank, $topPickRank, $hasBuyLink),
        ];
    }

    /**
     * Composite 0-100 score weighting the analysis dimensions.
     *
     * Heuristic — favoring the signals most directly correlated with
     * "would-this-drive-a-sale":
     *   - absent / negative → 0
     *   - neutral mention → ~15-30 depending on position
     *   - recommended mention → ~45-70 depending on position
     *   - top-3 pick → +15 boost, scaled by rank (1 > 2 > 3)
     *   - buy link present → +10 boost
     */
    private function compositeScore(string $mentionType, $positionRank, $topPickRank, bool $hasBuyLink): float
    {
        if ($mentionType === self::MENTION_ABSENT || $mentionType === self::MENTION_NEGATIVE) {
            return 0.0;
        }

        $base = match ($mentionType) {
            self::MENTION_RECOMMENDED => 55.0,
            self::MENTION_NEUTRAL => 20.0,
            default => 0.0,
        };

        // Earlier position → higher score. Position 1 is best.
        if (is_numeric($positionRank) && $positionRank > 0) {
            $positionBonus = max(0.0, 15.0 - (($positionRank - 1) * 3.0));
            $base += $positionBonus;
        }

        // Top-pick rank boost: rank 1 → +15, rank 2 → +10, rank 3 → +5
        if (is_numeric($topPickRank) && $topPickRank >= 1 && $topPickRank <= 3) {
            $base += match ((int) $topPickRank) {
                1 => 15.0,
                2 => 10.0,
                3 => 5.0,
                default => 0.0,
            };
        }

        if ($hasBuyLink) {
            $base += 10.0;
        }

        return min(100.0, round($base, 2));
    }

    private function absentResult(string $reason): array
    {
        return [
            'mention_type' => self::MENTION_ABSENT,
            'position_rank' => null,
            'is_top_pick' => false,
            'top_pick_rank' => null,
            'has_buy_link' => false,
            'reasoning' => $reason,
            'strength_score' => 0.0,
        ];
    }

    private function buildPrompt(string $aiResponse, string $domain, array $brandList): string
    {
        $brands = implode(', ', array_map(fn ($b) => "\"{$b}\"", $brandList));
        // Truncate AI response if extremely long to keep token cost down
        $truncated = mb_strlen($aiResponse) > 6000
            ? mb_substr($aiResponse, 0, 6000)."\n\n[response truncated]"
            : $aiResponse;

        return <<<PROMPT
You are analyzing an AI assistant's response to score how strongly a specific brand was recommended.

The brand to evaluate: domain "{$domain}", brand name(s): {$brands}.

The AI's response to score:
---
{$truncated}
---

Analyze the response and return a single JSON object (no other text, no markdown fences) with exactly these fields:

{
  "mention_type": one of "recommended" | "neutral" | "negative" | "absent",
    // "recommended" = AI describes this brand favorably or includes it in a recommendation list
    // "neutral" = brand is mentioned without endorsement (just named, listed alongside competitors)
    // "negative" = AI describes the brand unfavorably, warns against it, or notes problems
    // "absent" = brand not mentioned at all in the response
  "position_rank": integer or null,
    // Approximate position the brand first appears among ALL brands mentioned (1 = first brand named).
    // null if absent.
  "top_pick_rank": integer or null,
    // If the response includes a ranked list of recommendations (e.g. "Top 3:", "1. ... 2. ... 3. ..."),
    // the rank position of this brand in that list. null if not in a ranked list or absent.
  "has_buy_link": boolean,
    // Did the response include a buy/store/product URL for this brand (not just a domain mention)?
  "reasoning": short string (max 200 chars) summarizing why you picked the mention_type.
}

Return only the JSON object.
PROMPT;
    }

    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        // Strip markdown code fences if Claude wrapped the response
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        // Fallback: try to find the first {...} block
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }
}
