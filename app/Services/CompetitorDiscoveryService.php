<?php

namespace App\Services;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovers competitor websites using AI analysis of scanned content.
 *
 * Uses OpenAI to analyze page content and identify similar competitor
 * websites that can be scanned for benchmarking purposes.
 */
class CompetitorDiscoveryService
{
    /**
     * Maximum number of competitors to discover per scan.
     */
    private const MAX_COMPETITORS = 5;

    public function __construct(
        private ScanService $scanService,
        private TokenService $tokenService,
    ) {}

    /**
     * Discover and scan competitors for a given scan.
     */
    public function discoverAndScanCompetitors(Scan $scan): array
    {
        if (! $scan->auto_find_competitors || $scan->requested_tier !== 'full') {
            return [];
        }

        // Update status to discovering
        $scan->update(['competitor_discovery_status' => 'discovering']);

        $content = Arr::get($scan->results, 'content_summary', '');
        $url = $scan->url;
        $title = $scan->title ?? parse_url($url, PHP_URL_HOST);

        // Extract more context from results if available
        $pillars = Arr::get($scan->results, 'pillars', []);
        $summary = Arr::get($scan->results, 'summary.overall', '');

        try {
            $competitors = $this->discoverCompetitors($url, $title, $content, $summary, $pillars);

            if (empty($competitors)) {
                Log::info('No competitors discovered for scan', ['scan_id' => $scan->id, 'url' => $url]);
                $scan->update([
                    'competitor_discovery_status' => 'completed',
                    'competitors_found' => 0,
                ]);

                // Refund all competitor tokens since none were found
                $this->refundUnusedCompetitorTokens($scan, 0);

                return [];
            }

            // Update status to scanning
            $scan->update(['competitor_discovery_status' => 'scanning']);

            Log::info('Creating competitor scans', [
                'scan_id' => $scan->id,
                'discovered_urls' => count($competitors),
                'urls' => $competitors,
            ]);

            $createdScans = $this->createCompetitorScans($scan, $competitors);

            Log::info('Competitor scans creation result', [
                'scan_id' => $scan->id,
                'created_count' => count($createdScans),
            ]);

            // If no scans were created (all competitors already exist or limit reached),
            // mark as completed immediately
            if (empty($createdScans)) {
                Log::info('No new competitor scans created - marking as completed', [
                    'scan_id' => $scan->id,
                    'discovered_urls' => count($competitors),
                ]);
                $scan->update([
                    'competitor_discovery_status' => 'completed',
                    'competitors_found' => 0,
                ]);

                // Refund all competitor tokens since none were created
                $this->refundUnusedCompetitorTokens($scan, 0);

                return [];
            }

            // Update with count - status stays 'scanning' until all competitor scans complete
            $scan->update([
                'competitors_found' => count($createdScans),
            ]);

            // Refund tokens for competitors not created (prepaid for 5, refund for unused)
            $this->refundUnusedCompetitorTokens($scan, count($createdScans));

            Log::info('Competitor discovery completed', [
                'parent_scan_id' => $scan->id,
                'competitors_found' => count($createdScans),
            ]);

            return $createdScans;
        } catch (\Exception $e) {
            Log::error('Competitor discovery failed', [
                'scan_id' => $scan->id,
                'error' => $e->getMessage(),
            ]);

            $scan->update(['competitor_discovery_status' => 'failed']);

            // Refund all competitor tokens on failure
            $this->refundUnusedCompetitorTokens($scan, 0);

            throw $e;
        }
    }

    /**
     * Use AI to discover competitor URLs based on the scanned content.
     */
    private function discoverCompetitors(
        string $url,
        string $title,
        string $content,
        string $summary,
        array $pillars
    ): array {
        $apiKey = config('rag.openai.api_key');
        if (empty($apiKey)) {
            Log::warning('OpenAI API key not configured for competitor discovery');

            return [];
        }

        $domain = parse_url($url, PHP_URL_HOST);

        $prompt = $this->buildDiscoveryPrompt($domain, $title, $content, $summary, $pillars);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('rag.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a competitive market analyst. Given information about a business website, identify other companies that compete in the SAME market for the SAME customers. Focus on businesses that offer similar products or services to the same target audience. Only return real, well-known company websites.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            if (! $response->successful()) {
                Log::error('OpenAI API error for competitor discovery', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $result = $response->json();
            $responseText = Arr::get($result, 'choices.0.message.content', '');

            return $this->parseCompetitorUrls($responseText, $domain);
        } catch (\Exception $e) {
            Log::error('Exception during competitor discovery', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return [];
        }
    }

    /**
     * Build the prompt for competitor discovery.
     */
    private function buildDiscoveryPrompt(
        string $domain,
        string $title,
        string $content,
        string $summary,
        array $pillars
    ): string {
        $contextParts = ["Website: {$domain}", "Title: {$title}"];

        if (! empty($summary)) {
            $contextParts[] = "Summary: {$summary}";
        }

        if (! empty($content)) {
            $truncatedContent = substr($content, 0, 1500);
            $contextParts[] = "Content excerpt: {$truncatedContent}";
        }

        // Extract topics from pillar data
        $topics = [];
        foreach ($pillars as $pillarData) {
            if (isset($pillarData['details']['topics'])) {
                $topics = array_merge($topics, (array) $pillarData['details']['topics']);
            }
        }
        if (! empty($topics)) {
            $contextParts[] = 'Topics covered: '.implode(', ', array_slice($topics, 0, 10));
        }

        $context = implode("\n", $contextParts);

        return <<<PROMPT
I need to find business competitors for the following website. These are companies that {$domain} competes against for customers.

{$context}

Based on this information:
1. First, determine what product/service/content this website offers
2. Then identify up to 5 companies that offer SIMILAR products/services to the SAME target market

Requirements:
- Return ONLY real, established company websites (no placeholder URLs)
- Competitors must sell to the same customer base as {$domain}
- Exclude {$domain} itself
- Focus on well-known industry players and direct competitors
- If this is a SaaS product, find other SaaS products in that space
- If this is an e-commerce site, find other retailers selling similar products
- If this is a service business, find other companies offering similar services

Return competitor URLs in this exact format, one per line:
URL: https://competitor1.com
URL: https://competitor2.com

Only return URLs, no explanations.
PROMPT;
    }

    /**
     * Parse competitor URLs from the AI response.
     */
    private function parseCompetitorUrls(string $responseText, string $excludeDomain): array
    {
        $urls = [];
        $lines = explode("\n", $responseText);

        foreach ($lines as $line) {
            $line = trim($line);

            // Extract URL from "URL: https://..." format
            if (preg_match('/^URL:\s*(https?:\/\/[^\s]+)/i', $line, $matches)) {
                $url = rtrim($matches[1], '.,;');
                $urls[] = $url;
            } elseif (preg_match('/^(https?:\/\/[^\s]+)/i', $line, $matches)) {
                // Also accept plain URLs
                $url = rtrim($matches[1], '.,;');
                $urls[] = $url;
            }
        }

        // Filter and validate URLs
        $validUrls = [];
        foreach ($urls as $url) {
            // Skip if same domain as original
            $urlDomain = parse_url($url, PHP_URL_HOST);
            if ($urlDomain && stripos($urlDomain, $excludeDomain) !== false) {
                continue;
            }

            // Skip placeholder/example domains
            if (preg_match('/(example|test|localhost|placeholder)/i', $urlDomain ?? '')) {
                continue;
            }

            // Validate URL format
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $validUrls[] = $url;
            }
        }

        return array_slice(array_unique($validUrls), 0, self::MAX_COMPETITORS);
    }

    /**
     * Create competitor scans from discovered URLs.
     */
    private function createCompetitorScans(Scan $parentScan, array $competitorUrls): array
    {
        $createdScans = [];

        foreach ($competitorUrls as $url) {
            // Check if competitor already exists for this team/user
            $existingCompetitor = Scan::where('url', $url)
                ->where('user_id', $parentScan->user_id)
                ->where('team_id', $parentScan->team_id)
                ->where('is_competitor', true)
                ->where('status', 'completed')
                ->first();

            if ($existingCompetitor) {
                Log::info('Competitor scan already exists', [
                    'url' => $url,
                    'existing_scan_id' => $existingCompetitor->id,
                ]);

                continue;
            }

            // Check competitor limit
            $teamId = $parentScan->team_id;
            $userId = $parentScan->user_id;

            if (! Scan::canAddCompetitor($teamId, $userId)) {
                Log::info('Competitor limit reached, skipping', [
                    'url' => $url,
                    'team_id' => $teamId,
                    'user_id' => $userId,
                ]);

                break;
            }

            // Use the competitor tier from parent scan (defaults to 'basic')
            $competitorTier = $parentScan->competitor_scan_tier ?? 'basic';

            // Create the competitor scan with the selected tier
            $competitorScan = Scan::create([
                'user_id' => $parentScan->user_id,
                'team_id' => $parentScan->team_id,
                'url' => $url,
                'title' => parse_url($url, PHP_URL_HOST),
                'status' => 'pending',
                'requested_tier' => $competitorTier,
                'is_competitor' => true,
                'parent_scan_id' => $parentScan->id,
                'tokens_charged' => false, // Tokens were charged on the parent scan
                'tokens_amount' => 0,
            ]);

            // Dispatch the scan job
            ScanWebsiteJob::dispatch($competitorScan)->delay(now()->addSeconds(count($createdScans) * 3));

            $createdScans[] = $competitorScan;

            Log::info('Created competitor scan', [
                'scan_id' => $competitorScan->id,
                'url' => $url,
                'parent_scan_id' => $parentScan->id,
                'tier' => $competitorTier,
            ]);
        }

        return $createdScans;
    }

    /**
     * Refund tokens for competitor scans that weren't created.
     *
     * Only the per-scan costs are refundable for unused slots.
     * The AI discovery base fee is non-refundable (covers API costs).
     */
    private function refundUnusedCompetitorTokens(Scan $scan, int $competitorsCreated): void
    {
        $competitorTier = $scan->competitor_scan_tier ?? 'basic';
        $refundAmount = $this->scanService->getRefundableCompetitorTokens($competitorTier, $competitorsCreated);

        if ($refundAmount <= 0) {
            return;
        }

        $user = User::find($scan->user_id);
        if (! $user) {
            Log::warning('Could not refund competitor tokens - user not found', [
                'scan_id' => $scan->id,
                'user_id' => $scan->user_id,
            ]);

            return;
        }

        $unusedSlots = self::MAX_COMPETITORS - $competitorsCreated;

        $this->tokenService->refund($user, $refundAmount, 'Refund for unused competitor scan slots', [
            'scan_id' => $scan->id,
            'competitors_created' => $competitorsCreated,
            'unused_slots' => $unusedSlots,
            'competitor_tier' => $competitorTier,
        ]);

        // Update the scan's token amount to reflect the refund
        $scan->update([
            'tokens_refunded' => ($scan->tokens_refunded ?? 0) + $refundAmount,
        ]);

        Log::info('Refunded unused competitor tokens', [
            'scan_id' => $scan->id,
            'competitors_created' => $competitorsCreated,
            'unused_slots' => $unusedSlots,
            'refund_amount' => $refundAmount,
            'competitor_tier' => $competitorTier,
            'ai_discovery_fee_retained' => ScanService::AI_DISCOVERY_FEE,
        ]);
    }
}
