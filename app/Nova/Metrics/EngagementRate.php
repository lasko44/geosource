<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

/**
 * Shows the percentage of page views where the visitor engaged (stayed 3+ seconds).
 */
class EngagementRate extends Value
{
    public $name = 'Engagement Rate';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        $range = $request->range ?? 30;

        $total = PageView::where('is_bot', false)
            ->where('created_at', '>=', now()->subDays($range))
            ->count();

        $engaged = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->where('created_at', '>=', now()->subDays($range))
            ->count();

        $rate = $total > 0 ? round(($engaged / $total) * 100, 1) : 0;

        $previousTotal = PageView::where('is_bot', false)
            ->whereBetween('created_at', [now()->subDays($range * 2), now()->subDays($range)])
            ->count();

        $previousEngaged = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->whereBetween('created_at', [now()->subDays($range * 2), now()->subDays($range)])
            ->count();

        $previousRate = $previousTotal > 0 ? round(($previousEngaged / $previousTotal) * 100, 1) : 0;

        return $this->result($rate)
            ->previous($previousRate)
            ->suffix('%');
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

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'engagement-rate';
    }
}
