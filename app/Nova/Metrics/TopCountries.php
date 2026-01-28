<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class TopCountries extends Partition
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Top Countries';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $results = PageView::where('is_bot', false)
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('country, count(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'country')
            ->toArray();

        return $this->result($results)->colors([
            'United States' => '#3B82F6',
            'United Kingdom' => '#EF4444',
            'Canada' => '#F97316',
            'Australia' => '#22C55E',
            'Germany' => '#000000',
            'France' => '#0055A4',
            'India' => '#FF9933',
            'Brazil' => '#009C3B',
            'Japan' => '#BC002D',
            'Netherlands' => '#FF6600',
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
