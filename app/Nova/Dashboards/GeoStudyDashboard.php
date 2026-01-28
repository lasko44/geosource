<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\GeoStudyAvgScore;
use App\Nova\Metrics\GeoStudyCategoryBreakdown;
use App\Nova\Metrics\GeoStudyCount;
use App\Nova\Metrics\GeoStudyGradeDistribution;
use App\Nova\Metrics\GeoStudyPillarBreakdown;
use Laravel\Nova\Dashboard;

class GeoStudyDashboard extends Dashboard
{
    /**
     * Get the displayable name of the dashboard.
     */
    public function name(): string
    {
        return 'GEO Studies';
    }

    /**
     * Get the URI key of the dashboard.
     */
    public function uriKey(): string
    {
        return 'geo-study-dashboard';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(): array
    {
        return [
            // Value Metrics
            (new GeoStudyCount)->width('1/3'),
            (new GeoStudyAvgScore)->width('1/3'),
            (new GeoStudyCategoryBreakdown)->width('1/3'),

            // Distribution Charts
            (new GeoStudyGradeDistribution)->width('1/2'),
            (new GeoStudyPillarBreakdown)->width('1/2'),
        ];
    }
}
