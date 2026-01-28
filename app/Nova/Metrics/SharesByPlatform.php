<?php

namespace App\Nova\Metrics;

use App\Models\BlogShare;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class SharesByPlatform extends Partition
{
    /**
     * The displayable name of the metric.
     */
    public function name(): string
    {
        return 'Shares by Platform';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count(
            $request,
            BlogShare::where('created_at', '>=', now()->subDays(30)),
            'platform'
        )->label(fn ($value) => match ($value) {
            'twitter' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            'facebook' => 'Facebook',
            'copy_link' => 'Copy Link',
            default => ucfirst($value ?? 'Unknown'),
        })->colors([
            'X (Twitter)' => '#000000',
            'LinkedIn' => '#0077B5',
            'Facebook' => '#1877F2',
            'Copy Link' => '#22c55e',
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
