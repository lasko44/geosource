<?php

namespace App\Nova\Metrics;

use App\Models\ExperimentParticipant;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Laravel\Nova\Nova;

/**
 * Shows daily experiment participant assignments.
 */
class ExperimentParticipantsPerDay extends Trend
{
    public $name = 'Experiment Participants';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        return $this->countByDays($request, ExperimentParticipant::class)->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array<int|string, string>
     */
    public function ranges(): array
    {
        return [
            7 => Nova::__('7 Days'),
            14 => Nova::__('14 Days'),
            30 => Nova::__('30 Days'),
            60 => Nova::__('60 Days'),
        ];
    }

    /**
     * Get the timezone for the metric.
     */
    public function timezone(NovaRequest $request): string
    {
        return 'America/Chicago';
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
        return 'experiment-participants-per-day';
    }
}
