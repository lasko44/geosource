<?php

namespace App\Nova\Metrics;

use App\Models\FailedJob;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class FailedJobsCount extends Value
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Failed Jobs';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->result(FailedJob::count())->allowZeroResult();
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
        return 'failed-jobs-count';
    }
}
