<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;
use Illuminate\Support\Facades\Http;

/**
 * Scores content based on AI crawler accessibility.
 *
 * PRO TIER FEATURE
 *
 * Measures:
 * - robots.txt AI bot rules
 * - AI-specific meta tags
 * - Crawlability signals
 */
class AIAccessibilityScorer implements ScorerInterface
{
    private const MAX_SCORE = 8;

    private const AI_BOTS = [
        'GPTBot' => 'OpenAI (ChatGPT)',
        'ChatGPT-User' => 'ChatGPT Browse',
        'Google-Extended' => 'Google AI/Bard',
        'anthropic-ai' => 'Anthropic (Claude)',
        'Claude-Web' => 'Claude Browse',
        'CCBot' => 'Common Crawl',
        'PerplexityBot' => 'Perplexity AI',
        'Amazonbot' => 'Amazon/Alexa',
        'FacebookBot' => 'Meta AI',
        'Bytespider' => 'ByteDance AI',
        'Applebot-Extended' => 'Apple AI',
        'cohere-ai' => 'Cohere',
    ];

    public function score(string $content, array $context = []): array
    {
        $url = $context['url'] ?? null;

        $details = [
            'robots_txt' => $this->analyzeRobotsTxt($url),
            'meta_robots' => $this->analyzeMetaRobots($content),
            'ai_meta_tags' => $this->analyzeAIMetaTags($content),
        ];

        // Calculate scores (total: 8 points)
        $robotsTxtScore = $this->calculateRobotsTxtScore($details['robots_txt']);   // Up to 5 pts
        $metaScore = $this->calculateMetaScore($details['meta_robots']);             // Up to 2 pts
        $aiMetaScore = $this->calculateAIMetaScore($details['ai_meta_tags']);        // Up to 1 pt

        $totalScore = $robotsTxtScore + $metaScore + $aiMetaScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'robots_txt' => $robotsTxtScore,
                    'meta_robots' => $metaScore,
                    'ai_meta_tags' => $aiMetaScore,
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
        return 'AI Crawler Access';
    }

    private function analyzeRobotsTxt(?string $url): array
    {
        $result = [
            'exists' => false,
            'url' => null,
            'ai_bots_status' => [],
            'allows_all_ai' => true,
            'blocks_all_ai' => false,
            'partially_blocked' => false,
            'has_sitemap' => false,
            'sitemap_url' => null,
            'error' => null,
        ];

        if (empty($url)) {
            $result['error'] = 'No URL provided';

            return $result;
        }

        $parsed = parse_url($url);
        if (empty($parsed['scheme']) || empty($parsed['host'])) {
            $result['error'] = 'Invalid URL';

            return $result;
        }

        $robotsUrl = $parsed['scheme'].'://'.$parsed['host'].'/robots.txt';
        $result['url'] = $robotsUrl;

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'GeoSource Scanner/1.0'])
                ->get($robotsUrl);

            if (! $response->successful()) {
                // No robots.txt means all bots are allowed
                $result['error'] = 'File not found (HTTP '.$response->status().')';
                $result['allows_all_ai'] = true;

                return $result;
            }

            $result['exists'] = true;
            $content = $response->body();

            // Parse robots.txt content
            $result = $this->parseRobotsTxt($content, $result);

        } catch (\Exception $e) {
            $result['error'] = 'Failed to fetch: '.$e->getMessage();
            // Assume allowed if can't fetch
            $result['allows_all_ai'] = true;
        }

        return $result;
    }

    private function parseRobotsTxt(string $content, array $result): array
    {
        $lines = explode("\n", $content);
        $currentUserAgent = null;
        $rules = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse directive
            if (preg_match('/^(User-agent|Disallow|Allow|Sitemap)\s*:\s*(.*)$/i', $line, $match)) {
                $directive = strtolower($match[1]);
                $value = trim($match[2]);

                if ($directive === 'user-agent') {
                    $currentUserAgent = $value;
                    if (! isset($rules[$currentUserAgent])) {
                        $rules[$currentUserAgent] = ['allow' => [], 'disallow' => []];
                    }
                } elseif ($directive === 'sitemap') {
                    $result['has_sitemap'] = true;
                    $result['sitemap_url'] = $value;
                } elseif ($currentUserAgent !== null) {
                    if ($directive === 'disallow') {
                        $rules[$currentUserAgent]['disallow'][] = $value;
                    } elseif ($directive === 'allow') {
                        $rules[$currentUserAgent]['allow'][] = $value;
                    }
                }
            }
        }

        // Check each AI bot
        $blockedBots = [];
        $allowedBots = [];

        foreach (self::AI_BOTS as $botName => $description) {
            $status = $this->checkBotStatus($botName, $rules);
            $result['ai_bots_status'][$botName] = [
                'name' => $description,
                'allowed' => $status,
            ];

            if ($status) {
                $allowedBots[] = $botName;
            } else {
                $blockedBots[] = $botName;
            }
        }

        $result['blocked_bots'] = $blockedBots;
        $result['allowed_bots'] = $allowedBots;
        $result['allows_all_ai'] = empty($blockedBots);
        $result['blocks_all_ai'] = empty($allowedBots);
        $result['partially_blocked'] = ! empty($blockedBots) && ! empty($allowedBots);

        return $result;
    }

    private function checkBotStatus(string $botName, array $rules): bool
    {
        // Check specific bot rules first
        if (isset($rules[$botName])) {
            $botRules = $rules[$botName];
            // If there's a disallow for root, bot is blocked
            if (in_array('/', $botRules['disallow'])) {
                return false;
            }
            // If there's explicit allow or no disallow, bot is allowed
            if (! empty($botRules['allow']) || empty($botRules['disallow'])) {
                return true;
            }
        }

        // Check wildcard rules
        if (isset($rules['*'])) {
            $wildcardRules = $rules['*'];
            if (in_array('/', $wildcardRules['disallow'])) {
                return false;
            }
        }

        // Default: allowed
        return true;
    }

    private function analyzeMetaRobots(string $content): array
    {
        $result = [
            'has_meta_robots' => false,
            'noindex' => false,
            'nofollow' => false,
            'noarchive' => false,
            'nosnippet' => false,
            'noimageindex' => false,
            'directives' => [],
        ];

        // Check for meta robots tag
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)["\']/', $content, $match) ||
            preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']robots["\']/', $content, $match)) {
            $result['has_meta_robots'] = true;
            $directives = strtolower($match[1]);
            $result['directives'] = array_map('trim', explode(',', $directives));

            $result['noindex'] = str_contains($directives, 'noindex');
            $result['nofollow'] = str_contains($directives, 'nofollow');
            $result['noarchive'] = str_contains($directives, 'noarchive');
            $result['nosnippet'] = str_contains($directives, 'nosnippet');
            $result['noimageindex'] = str_contains($directives, 'noimageindex');
        }

        // Check for X-Robots-Tag in meta (sometimes used)
        if (preg_match('/<meta[^>]+http-equiv=["\']X-Robots-Tag["\'][^>]+content=["\']([^"\']+)["\']/', $content, $match)) {
            $result['has_meta_robots'] = true;
            $directives = strtolower($match[1]);

            if (str_contains($directives, 'noindex')) {
                $result['noindex'] = true;
            }
            if (str_contains($directives, 'nosnippet')) {
                $result['nosnippet'] = true;
            }
        }

        return $result;
    }

    private function analyzeAIMetaTags(string $content): array
    {
        $result = [
            'has_ai_specific_tags' => false,
            'tags_found' => [],
        ];

        // Check for AI-specific meta tags (emerging standards)
        $aiMetaPatterns = [
            'ai-content-declaration' => '/<meta[^>]+name=["\']ai-content-declaration["\'][^>]+content=["\']([^"\']+)["\']/',
            'ai-training-opt-out' => '/<meta[^>]+name=["\']ai-training-opt-out["\']/',
            'robots-ai' => '/<meta[^>]+name=["\']robots-ai["\'][^>]+content=["\']([^"\']+)["\']/',
        ];

        foreach ($aiMetaPatterns as $name => $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $result['has_ai_specific_tags'] = true;
                $result['tags_found'][$name] = $match[1] ?? 'present';
            }
        }

        return $result;
    }

    private function calculateRobotsTxtScore(array $robots): float
    {
        $score = 0;

        // robots.txt exists and is parseable
        if ($robots['exists']) {
            $score += 1;
        }

        // AI bots are allowed
        if ($robots['allows_all_ai']) {
            $score += 3;
        } elseif ($robots['partially_blocked']) {
            // Partial access - count allowed major bots
            $majorBots = ['GPTBot', 'Google-Extended', 'anthropic-ai', 'PerplexityBot'];
            $allowedMajor = 0;
            foreach ($majorBots as $bot) {
                if (isset($robots['ai_bots_status'][$bot]) && $robots['ai_bots_status'][$bot]['allowed']) {
                    $allowedMajor++;
                }
            }
            $score += ($allowedMajor / count($majorBots)) * 2;
        }
        // blocks_all_ai = 0 points

        // Has sitemap reference
        if ($robots['has_sitemap']) {
            $score += 1;
        }

        return min(5, $score);
    }

    private function calculateMetaScore(array $meta): float
    {
        $score = 2; // Start with full points

        // Deduct for restrictive directives
        if ($meta['noindex']) {
            $score -= 1;
        }

        if ($meta['nosnippet']) {
            $score -= 0.5;
        }

        if ($meta['noarchive']) {
            $score -= 0.25;
        }

        return max(0, $score);
    }

    private function calculateAIMetaScore(array $aiMeta): float
    {
        // Bonus for having AI-specific meta tags (forward-thinking)
        if ($aiMeta['has_ai_specific_tags']) {
            return 1;
        }

        return 0.5; // Base score for not blocking
    }
}
