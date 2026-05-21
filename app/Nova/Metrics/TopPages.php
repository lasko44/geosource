<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Shows the top pages by engaged view count across the entire site.
 */
class TopPages extends Partition
{
    public $name = 'Top Pages (All)';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $results = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('path, count(*) as count')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(15)
            ->pluck('count', 'path')
            ->mapWithKeys(function (int $count, string $path): array {
                $label = $path === '/' ? 'Homepage' : $path;
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
        return 'top-pages';
    }
}
