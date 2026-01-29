<?php

namespace App\Nova\Metrics;

use App\Models\BlogShare;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class BlogSharesPerDay extends Trend
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Shares per Day';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        $range = $request->range ?? 7;

        $results = BlogShare::query()
            ->select(
                DB::raw("DATE(CONVERT_TZ(created_at, '+00:00', '-06:00')) as date"),
                DB::raw('COUNT(*) as aggregate')
            )
            ->where('created_at', '>=', now()->subDays($range)->startOfDay()->utc())
            ->groupBy(DB::raw("DATE(CONVERT_TZ(created_at, '+00:00', '-06:00'))"))
            ->orderBy('date')
            ->get()
            ->pluck('aggregate', 'date')
            ->toArray();

        $trend = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[$date] = $results[$date] ?? 0;
        }

        return (new TrendResult)->trend($trend)->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array<int, string>
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
