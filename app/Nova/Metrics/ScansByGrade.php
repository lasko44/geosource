<?php

namespace App\Nova\Metrics;

use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use App\Models\Scan;
use Laravel\Nova\Metrics\PartitionResult;

class ScansByGrade extends Partition
{
    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, Scan::class, 'grade')->colors([
            'A+' => '#22c55e',
            'A' => '#22c55e',
            'A-' => '#4ade80',
            'B+' => '#3b82f6',
            'B' => '#3b82f6',
            'B-' => '#60a5fa',
            'C+' => '#f59e0b',
            'C' => '#f59e0b',
            'C-' => '#fbbf24',
            'D+' => '#ef4444',
            'D' => '#ef4444',
            'D-' => '#f87171',
            'F' => '#dc2626',
        ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): DateTimeInterface|null
    {
        // return now()->addMinutes(5);

        return null;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'scans-by-grade';
    }
}
