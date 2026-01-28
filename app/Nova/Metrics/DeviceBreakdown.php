<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class DeviceBreakdown extends Partition
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Devices';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count(
            $request,
            PageView::where('is_bot', false)
                ->where('created_at', '>=', now()->subDays(30)),
            'device_type'
        )->label(fn ($value) => match ($value) {
            'desktop' => 'Desktop',
            'mobile' => 'Mobile',
            'tablet' => 'Tablet',
            default => ucfirst($value ?? 'Unknown'),
        })->colors([
            'Desktop' => '#6366f1',
            'Mobile' => '#22c55e',
            'Tablet' => '#f59e0b',
            'Unknown' => '#94a3b8',
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
