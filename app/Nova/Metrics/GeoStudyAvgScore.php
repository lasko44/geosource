<?php

namespace App\Nova\Metrics;

use App\Models\GeoStudyResult;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;
use Laravel\Nova\Nova;

class GeoStudyAvgScore extends Value
{
    public $name = 'Average Score';

    public function calculate(NovaRequest $request): ValueResult
    {
        return $this->average(
            $request,
            GeoStudyResult::where('status', 'completed'),
            'percentage'
        )->suffix('%');
    }

    public function ranges(): array
    {
        return [
            30 => Nova::__('30 Days'),
            60 => Nova::__('60 Days'),
            365 => Nova::__('365 Days'),
            'TODAY' => Nova::__('Today'),
            'MTD' => Nova::__('Month To Date'),
            'YTD' => Nova::__('Year To Date'),
        ];
    }

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'geo-study-avg-score';
    }
}
