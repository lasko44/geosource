<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class UniqueVisitorsPerDay extends Trend
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Unique Visitors';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        $range = $request->range ?? 7;

        // Get unique visitors per day using proper DISTINCT counting
        $results = PageView::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT visitor_hash) as aggregate'))
            ->where('is_bot', false)
            ->whereNotNull('engaged_at') // Only count engaged visitors
            ->where('created_at', '>=', now()->subDays($range))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('aggregate', 'date')
            ->toArray();

        // Fill in missing dates with 0
        $trend = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[$date] = $results[$date] ?? 0;
        }

        return (new TrendResult)->trend($trend)->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     */
    public function ranges(): array
    {
        return [
            7 => '7 Days',
            14 => '14 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): \DateInterval
    {
        return new \DateInterval('PT5M');
    }
}
