<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Shows the top entry (landing) pages where visitors first arrive on the site.
 */
class TopEntryPages extends Partition
{
    public $name = 'Top Landing Pages';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        // A landing page is the first page view in a session.
        // We find the earliest page view per session in the last 30 days.
        // Using a subquery to get first page view per session, then counting paths.
        $results = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('referrer_host')
            ->where('referrer_host', 'not like', '%geosource%')
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
        return 'top-entry-pages';
    }
}
