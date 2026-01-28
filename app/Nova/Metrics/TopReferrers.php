<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class TopReferrers extends Partition
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Top Referrers';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $results = PageView::where('is_bot', false)
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->where('referrer_host', 'not like', '%geosource%')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('referrer_host, count(*) as count')
            ->groupBy('referrer_host')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'referrer_host')
            ->toArray();

        // Add "Direct" for views without referrer
        $directCount = PageView::where('is_bot', false)
            ->whereNull('referrer_host')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($directCount > 0) {
            $results = array_merge(['Direct' => $directCount], $results);
        }

        return $this->result($results)->colors([
            'Direct' => '#6366f1',
            'google.com' => '#22c55e',
            'www.google.com' => '#22c55e',
            't.co' => '#1DA1F2',
            'twitter.com' => '#1DA1F2',
            'x.com' => '#000000',
            'facebook.com' => '#1877F2',
            'linkedin.com' => '#0A66C2',
            'reddit.com' => '#FF4500',
        ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): \DateInterval
    {
        return new \DateInterval('PT5M');
    }
}
