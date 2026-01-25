<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\FailedJobsCount;
use App\Nova\Metrics\NewUsersThisWeek;
use App\Nova\Metrics\PendingJobsCount;
use App\Nova\Metrics\ScansByGrade;
use App\Nova\Metrics\ScansPerDay;
use App\Nova\Metrics\ScheduledScansCount;
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
            // User & Scan Stats
            new TotalUsers,
            new TotalScans,
            new NewUsersThisWeek,

            // Queue Stats
            new PendingJobsCount,
            new FailedJobsCount,
            new ScheduledScansCount,

            // Charts
            new ScansPerDay,
            new ScansByGrade,
        ];
    }
}
