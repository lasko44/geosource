<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Shows the top performing programmatic SEO pages by engaged views.
 */
class TopProgrammaticPages extends Partition
{
    public $name = 'Top SEO Pages';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $seoPageTypes = ['industry_geo', 'platform_optimize', 'comparison', 'how_to', 'resource_article', 'geo_page'];

        $results = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->whereIn('page_type', $seoPageTypes)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('path, count(*) as count')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(15)
            ->pluck('count', 'path')
            ->mapWithKeys(function (int $count, string $path): array {
                $label = $path;
                if (mb_strlen($label) > 45) {
                    $label = mb_substr($label, 0, 42).'...';
                }

                return [$label => $count];
            })
            ->toArray();

        return $this->result($results);
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
        return 'top-programmatic-pages';
    }
}
