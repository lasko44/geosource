<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Carbon\CarbonImmutable;
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
        $timezone = 'America/Chicago';

        // Get unique visitors per day using proper DISTINCT counting
        $results = PageView::query()
            ->select(
                DB::raw("DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE '{$timezone}') as date"),
                DB::raw('COUNT(DISTINCT visitor_hash) as aggregate')
            )
            ->where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->where('created_at', '>=', CarbonImmutable::now($timezone)->subDays($range)->startOfDay())
            ->groupBy(DB::raw("DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE '{$timezone}')"))
            ->orderBy('date')
            ->pluck('aggregate', 'date')
            ->toArray();

        // Fill in missing dates with 0
        $trend = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = CarbonImmutable::now($timezone)->subDays($i)->format('Y-m-d');
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
