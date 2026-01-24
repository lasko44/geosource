<?php

use App\Jobs\ProcessScheduledCitationChecksJob;
use App\Jobs\SyncGA4DataJob;
use App\Models\GA4Connection;
use App\Models\GA4ReferralData;
use App\Models\CitationCheck;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Scans
|--------------------------------------------------------------------------
*/

// Run scheduled scans every 5 minutes
Schedule::command('scans:run-scheduled')->everyFiveMinutes();

/*
|--------------------------------------------------------------------------
| Citation Tracking Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Process scheduled citation checks every hour
Schedule::job(new ProcessScheduledCitationChecksJob)->hourly();

// Sync GA4 data for all active connections daily at 2 AM
Schedule::command('citations:sync-ga4')->dailyAt('02:00');

// Cleanup old data daily at 3 AM
Schedule::command('citations:cleanup')->dailyAt('03:00');

/*
|--------------------------------------------------------------------------
| Citation Console Commands
|--------------------------------------------------------------------------
*/

Artisan::command('citations:sync-ga4', function () {
    $this->info('Starting GA4 data sync for all active connections...');

    $connections = GA4Connection::where('is_active', true)->get();

    foreach ($connections as $connection) {
        $this->info("Dispatching sync for connection: {$connection->property_name}");
        SyncGA4DataJob::dispatch($connection);
    }

    $this->info("Dispatched sync jobs for {$connections->count()} connections.");
})->purpose('Sync GA4 referral data for all active connections');

Artisan::command('citations:cleanup', function () {
    $this->info('Cleaning up old citation data...');

    // Cleanup old citation checks
    $checkDays = config('citations.check.cleanup_after_days', 90);
    $checksDeleted = CitationCheck::where('created_at', '<', now()->subDays($checkDays))
        ->where('status', 'completed')
        ->delete();
    $this->info("Deleted {$checksDeleted} old citation checks (older than {$checkDays} days).");

    // Cleanup old GA4 referral data
    $ga4Days = config('citations.ga4_sync.cleanup_after_days', 365);
    $ga4Deleted = GA4ReferralData::where('date', '<', now()->subDays($ga4Days))->delete();
    $this->info("Deleted {$ga4Deleted} old GA4 referral records (older than {$ga4Days} days).");

    $this->info('Cleanup complete.');
})->purpose('Clean up old citation tracking data');
