<?php

namespace App\Nova\Metrics;

use App\Models\ScheduledScan;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class ScheduledScansCount extends Value
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Active Scheduled Scans';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->result(ScheduledScan::where('is_active', true)->count())->allowZeroResult();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [];
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'scheduled-scans-count';
    }
}
