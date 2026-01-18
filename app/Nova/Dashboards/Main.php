<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\NewUsersThisWeek;
use App\Nova\Metrics\ScansByGrade;
use App\Nova\Metrics\ScansPerDay;
use App\Nova\Metrics\TotalScans;
use App\Nova\Metrics\TotalUsers;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(): array
    {
        return [
            new TotalUsers,
            new TotalScans,
            new NewUsersThisWeek,
            new ScansPerDay,
            new ScansByGrade,
        ];
    }
}
