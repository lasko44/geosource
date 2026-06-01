<?php

namespace App\Http\Controllers;

use App\Models\IndustryBenchmark;
use App\Models\Scan;
use App\Models\CitationCheck;
use App\Models\GeoCorrelation;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves the public benchmark stats page showing aggregate GEO data.
 */
class BenchmarkController extends Controller
{
    public function __invoke(): Response
    {
        $stats = Cache::remember('public_benchmarks', 300, function () {
            $benchmarks = IndustryBenchmark::where('industry', '!=', 'all')
                ->where('sample_size', '>=', 2)
                ->orderByDesc('avg_citation_rate')
                ->get()
                ->map(fn ($b) => [
                    'industry' => ucfirst(str_replace('-', ' ', $b->industry)),
                    'sample_size' => $b->sample_size,
                    'avg_score' => $b->avg_geo_score,
                    'avg_citation_rate' => $b->avg_citation_rate,
                    'p25' => $b->p25_score,
                    'p50' => $b->p50_score,
                    'p75' => $b->p75_score,
                ])->all();

            $allBenchmark = IndustryBenchmark::where('industry', 'all')->first();

            return [
                'total_scans' => Scan::where('status', 'completed')->count(),
                'total_citations' => CitationCheck::where('status', CitationCheck::STATUS_COMPLETED)->count(),
                'total_correlations' => GeoCorrelation::withBothDataPoints()->count(),
                'unique_domains' => GeoCorrelation::distinct('domain')->count('domain'),
                'industries_count' => count($benchmarks),
                'overall_avg_score' => $allBenchmark?->avg_geo_score ?? 0,
                'overall_avg_citation_rate' => $allBenchmark?->avg_citation_rate ?? 0,
                'benchmarks' => $benchmarks,
            ];
        });

        return Inertia::render('Benchmarks/Index', [
            'stats' => $stats,
        ]);
    }
}
