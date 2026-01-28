<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\DeviceBreakdown;
use App\Nova\Metrics\FailedJobsCount;
use App\Nova\Metrics\NewUsersPerDay;
use App\Nova\Metrics\PageViewsPerDay;
use App\Nova\Metrics\PayingCustomers;
use App\Nova\Metrics\PendingJobsCount;
use App\Nova\Metrics\ScansByGrade;
use App\Nova\Metrics\ScansPerDay;
use App\Nova\Metrics\ScheduledScansCount;
use App\Nova\Metrics\TopBlogPosts;
use App\Nova\Metrics\TopCountries;
use App\Nova\Metrics\TopReferrers;
use App\Nova\Metrics\TotalScans;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\UniqueVisitorsPerDay;
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
            // Value Metrics
            (new TotalUsers)->width('1/4'),
            (new TotalScans)->width('1/4'),
            (new PendingJobsCount)->width('1/4'),
            (new FailedJobsCount)->width('1/4'),

            // Trend Charts
            (new NewUsersPerDay)->width('1/2'),
            (new PayingCustomers)->width('1/2'),
            (new ScansPerDay)->width('1/2'),
            (new TopBlogPosts)->width('1/2'),

            // Analytics
            (new PageViewsPerDay)->width('1/2'),
            (new UniqueVisitorsPerDay)->width('1/2'),
            (new TopReferrers)->width('1/3'),
            (new TopCountries)->width('1/3'),
            (new DeviceBreakdown)->width('1/3'),
        ];
    }
}
