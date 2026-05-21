<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

/**
 * Trend of engaged page views per day for programmatic SEO pages only.
 */
class SeoPageViewsPerDay extends Trend
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'SEO Page Views / Day';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        $seoPageTypes = ['industry_geo', 'platform_optimize', 'comparison', 'how_to', 'resource_article', 'geo_page', 'blog'];

        return $this->countByDays(
            $request,
            PageView::where('is_bot', false)
                ->whereNotNull('engaged_at')
                ->whereIn('page_type', $seoPageTypes)
        )->showLatestValue();
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
     * Get the timezone for the metric.
     */
    public function timezone(NovaRequest $request): string
    {
        return 'America/Chicago';
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): \DateInterval
    {
        return new \DateInterval('PT5M');
    }
}
