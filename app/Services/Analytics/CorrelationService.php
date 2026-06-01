<?php

namespace App\Services\Analytics;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\GeoCorrelation;
use App\Models\IndustryBenchmark;
use App\Models\Scan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Correlates GEO scan scores with AI citation outcomes to continuously
 * improve scoring accuracy and provide industry benchmarks.
 */
class CorrelationService
{
    /**
     * Record a correlation when a scan completes.
     */
    public function recordFromScan(Scan $scan): ?GeoCorrelation
    {
        if ($scan->status !== 'completed' || ! $scan->score) {
            return null;
        }

        $domain = parse_url($scan->url, PHP_URL_HOST);
        if (! $domain) {
            return null;
        }

        $domain = preg_replace('/^www\./', '', $domain);
        $results = $scan->results ?? [];

        // Check if we already have a correlation for this scan
        $existing = GeoCorrelation::where('scan_id', $scan->id)->first();
        if ($existing) {
            return $existing;
        }

        // Look for citation data for this domain
        $citationData = $this->findCitationDataForDomain($domain, $scan->user_id, $scan->visitor_id);

        $correlation = GeoCorrelation::create([
            'domain' => $domain,
            'url' => $scan->url,
            'scan_id' => $scan->id,
            'geo_score' => (int) round($scan->score),
            'geo_percentage' => Arr::get($results, 'percentage'),
            'geo_grade' => $scan->grade,
            'citation_readiness_score' => Arr::get($results, 'citation_readiness.score'),
            'content_type' => Arr::get($results, 'content_type.primary_type'),
            'pillar_scores' => $this->extractPillarPercentages($results),
            'citation_query_id' => $citationData['query_id'] ?? null,
            'query' => $citationData['query'] ?? null,
            'platforms_checked' => $citationData['checked'] ?? 0,
            'platforms_cited' => $citationData['cited'] ?? 0,
            'citation_rate' => $citationData['rate'] ?? null,
            'platforms_cited_list' => $citationData['platforms'] ?? null,
            'user_id' => $scan->user_id,
            'visitor_id' => $scan->visitor_id,
            'industry' => $this->detectIndustry($domain, $results),
            'source' => $scan->user_id ? 'user' : 'guest',
        ]);

        return $correlation;
    }

    /**
     * Record a correlation when citation checks complete.
     */
    public function recordFromCitation(CitationQuery $citationQuery): ?GeoCorrelation
    {
        $domain = $citationQuery->domain;
        if (! $domain) {
            return null;
        }

        $domain = preg_replace('/^www\./', '', $domain);

        // Get completed checks
        $checks = CitationCheck::where('citation_query_id', $citationQuery->id)
            ->where('status', CitationCheck::STATUS_COMPLETED)
            ->get();

        if ($checks->isEmpty()) {
            return null;
        }

        $cited = $checks->where('is_cited', true);
        $citationRate = round(($cited->count() / $checks->count()) * 100, 2);

        // Check if we already have a correlation for this citation query
        $existing = GeoCorrelation::where('citation_query_id', $citationQuery->id)->first();
        if ($existing) {
            // Update with citation data if scan was already recorded
            $existing->update([
                'platforms_checked' => $checks->count(),
                'platforms_cited' => $cited->count(),
                'citation_rate' => $citationRate,
                'platforms_cited_list' => $cited->pluck('platform')->values()->toArray(),
            ]);

            return $existing;
        }

        // Look for scan data for this domain
        $scanData = $this->findScanDataForDomain($domain, $citationQuery->user_id, $citationQuery->visitor_id);

        $correlation = GeoCorrelation::create([
            'domain' => $domain,
            'url' => $scanData['url'] ?? "https://{$domain}",
            'scan_id' => $scanData['scan_id'] ?? null,
            'geo_score' => $scanData['score'] ?? null,
            'geo_percentage' => $scanData['percentage'] ?? null,
            'geo_grade' => $scanData['grade'] ?? null,
            'citation_readiness_score' => $scanData['citation_readiness'] ?? null,
            'content_type' => $scanData['content_type'] ?? null,
            'pillar_scores' => $scanData['pillars'] ?? null,
            'citation_query_id' => $citationQuery->id,
            'query' => $citationQuery->query,
            'platforms_checked' => $checks->count(),
            'platforms_cited' => $cited->count(),
            'citation_rate' => $citationRate,
            'platforms_cited_list' => $cited->pluck('platform')->values()->toArray(),
            'user_id' => $citationQuery->user_id,
            'visitor_id' => $citationQuery->visitor_id,
            'industry' => $this->detectIndustry($domain, $scanData['results'] ?? []),
            'source' => $citationQuery->user_id ? 'user' : 'guest',
        ]);

        return $correlation;
    }

    /**
     * Refresh industry benchmarks from all correlation data.
     */
    public function refreshBenchmarks(): int
    {
        $correlations = GeoCorrelation::withBothDataPoints()->get();

        if ($correlations->isEmpty()) {
            return 0;
        }

        $byIndustry = $correlations->whereNotNull('industry')->groupBy('industry');
        $updated = 0;

        foreach ($byIndustry as $industry => $group) {
            if ($group->count() < 2) {
                continue;
            }

            $scores = $group->pluck('geo_score')->sort()->values();

            IndustryBenchmark::updateOrCreate(
                ['industry' => $industry],
                [
                    'sample_size' => $group->count(),
                    'avg_geo_score' => round($group->avg('geo_score'), 1),
                    'avg_citation_rate' => round($group->avg('citation_rate'), 2),
                    'avg_citation_readiness' => round($group->whereNotNull('citation_readiness_score')->avg('citation_readiness_score'), 1),
                    'dominant_content_type' => $group->whereNotNull('content_type')->groupBy('content_type')->sortByDesc(fn ($g) => $g->count())->keys()->first(),
                    'p25_score' => $scores->get((int) floor($scores->count() * 0.25)),
                    'p50_score' => $scores->get((int) floor($scores->count() * 0.50)),
                    'p75_score' => $scores->get((int) floor($scores->count() * 0.75)),
                    'data_source' => 'live',
                ]
            );
            $updated++;
        }

        // Also add the "all" benchmark
        IndustryBenchmark::updateOrCreate(
            ['industry' => 'all'],
            [
                'sample_size' => $correlations->count(),
                'avg_geo_score' => round($correlations->avg('geo_score'), 1),
                'avg_citation_rate' => round($correlations->avg('citation_rate'), 2),
                'avg_citation_readiness' => round($correlations->whereNotNull('citation_readiness_score')->avg('citation_readiness_score'), 1),
                'p25_score' => $correlations->pluck('geo_score')->sort()->values()->get((int) floor($correlations->count() * 0.25)),
                'p50_score' => $correlations->pluck('geo_score')->sort()->values()->get((int) floor($correlations->count() * 0.50)),
                'p75_score' => $correlations->pluck('geo_score')->sort()->values()->get((int) floor($correlations->count() * 0.75)),
                'data_source' => 'live',
            ]
        );

        return $updated + 1;
    }

    /**
     * Get benchmark data for a specific industry.
     */
    public function getBenchmark(string $industry): ?IndustryBenchmark
    {
        return IndustryBenchmark::where('industry', $industry)->first()
            ?? IndustryBenchmark::where('industry', 'all')->first();
    }

    /**
     * Get aggregate stats for the public benchmark page.
     */
    public function getPublicStats(): array
    {
        $total = GeoCorrelation::count();
        $withBoth = GeoCorrelation::withBothDataPoints()->count();
        $industries = GeoCorrelation::whereNotNull('industry')->distinct('industry')->count('industry');
        $domains = GeoCorrelation::distinct('domain')->count('domain');

        $benchmarks = IndustryBenchmark::where('industry', '!=', 'all')
            ->where('sample_size', '>=', 2)
            ->orderByDesc('avg_citation_rate')
            ->get();

        return [
            'total_data_points' => $total,
            'correlated_pairs' => $withBoth,
            'unique_domains' => $domains,
            'industries_tracked' => $industries,
            'total_scans' => Scan::where('status', 'completed')->count(),
            'total_citation_checks' => CitationCheck::where('status', CitationCheck::STATUS_COMPLETED)->count(),
            'benchmarks' => $benchmarks,
        ];
    }

    /**
     * Find citation data for a given domain.
     */
    private function findCitationDataForDomain(string $domain, ?int $userId, ?string $visitorId): array
    {
        $query = CitationQuery::where('domain', 'like', "%{$domain}%");

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($visitorId) {
            $query->where('visitor_id', $visitorId);
        } else {
            return [];
        }

        $citationQuery = $query->latest()->first();
        if (! $citationQuery) {
            return [];
        }

        $checks = CitationCheck::where('citation_query_id', $citationQuery->id)
            ->where('status', CitationCheck::STATUS_COMPLETED)
            ->get();

        if ($checks->isEmpty()) {
            return [];
        }

        $cited = $checks->where('is_cited', true);

        return [
            'query_id' => $citationQuery->id,
            'query' => $citationQuery->query,
            'checked' => $checks->count(),
            'cited' => $cited->count(),
            'rate' => round(($cited->count() / $checks->count()) * 100, 2),
            'platforms' => $cited->pluck('platform')->values()->toArray(),
        ];
    }

    /**
     * Find scan data for a given domain.
     */
    private function findScanDataForDomain(string $domain, ?int $userId, ?string $visitorId): array
    {
        $query = Scan::where('status', 'completed')
            ->where('url', 'like', "%{$domain}%");

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($visitorId) {
            $query->where('visitor_id', $visitorId);
        }

        $scan = $query->latest()->first();
        if (! $scan) {
            return [];
        }

        $results = $scan->results ?? [];

        return [
            'scan_id' => $scan->id,
            'url' => $scan->url,
            'score' => (int) round($scan->score),
            'percentage' => Arr::get($results, 'percentage'),
            'grade' => $scan->grade,
            'citation_readiness' => Arr::get($results, 'citation_readiness.score'),
            'content_type' => Arr::get($results, 'content_type.primary_type'),
            'pillars' => $this->extractPillarPercentages($results),
            'results' => $results,
        ];
    }

    /**
     * Extract pillar percentage scores from scan results.
     */
    private function extractPillarPercentages(array $results): ?array
    {
        $pillars = Arr::get($results, 'pillars');
        if (! $pillars) {
            return null;
        }

        $percentages = [];
        foreach ($pillars as $key => $pillar) {
            $percentages[$key] = Arr::get($pillar, 'percentage', 0);
        }

        return $percentages;
    }

    /**
     * Detect industry from domain and content analysis.
     */
    private function detectIndustry(string $domain, array $results): ?string
    {
        $contentType = Arr::get($results, 'content_type.primary_type');

        // Known domain-to-industry mappings can be expanded
        $domainIndustryMap = [
            'healthcare' => ['health', 'medical', 'clinic', 'hospital', 'doctor', 'dental', 'pharma', 'webmd', 'healthline', 'mayoclinic', 'zocdoc'],
            'finance' => ['bank', 'finance', 'invest', 'credit', 'loan', 'insurance', 'nerdwallet', 'bankrate', 'mercury', 'brex'],
            'saas' => ['app', 'software', 'cloud', 'platform', 'tool', 'notion', 'airtable', 'clickup', 'linear', 'figma'],
            'ecommerce' => ['shop', 'store', 'buy', 'retail', 'allbirds', 'casper', 'glossier', 'warby', 'chewy'],
            'travel' => ['travel', 'hotel', 'flight', 'booking', 'trip', 'airbnb', 'kayak', 'hostel', 'tripadvisor'],
            'education' => ['edu', 'learn', 'course', 'academy', 'school', 'coursera', 'khan', 'duolingo', 'brilliant'],
        ];

        foreach ($domainIndustryMap as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($domain, $keyword)) {
                    return $industry;
                }
            }
        }

        return $contentType === 'informational' ? 'informational' : null;
    }
}
