<?php

namespace App\Nova\Metrics;

use App\Models\GeoStudyResult;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class GeoStudyGradeDistribution extends Partition
{
    public $name = 'Grade Distribution';

    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count(
            $request,
            GeoStudyResult::where('status', 'completed'),
            'grade'
        )->colors([
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

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'geo-study-grade-distribution';
    }
}
