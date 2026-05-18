<?php

namespace App\Nova\Metrics;

use App\Models\ExperimentParticipant;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Shows conversion counts per experiment variant.
 */
class ExperimentConversionRate extends Partition
{
    public $name = 'Conversions by Variant';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, ExperimentParticipant::whereNotNull('converted_at'), 'variant')
            ->label(fn ($value) => ucfirst($value ?? 'Unknown'))
            ->colors([
                'control' => '#3b82f6',
                'scan_input' => '#22c55e',
            ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'experiment-conversion-rate';
    }
}
