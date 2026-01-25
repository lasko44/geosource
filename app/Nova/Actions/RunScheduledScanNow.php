<?php

namespace App\Nova\Actions;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class RunScheduledScanNow extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Run Scan Now';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $subscriptionService = app(SubscriptionService::class);

        foreach ($models as $scheduledScan) {
            /** @var ScheduledScan $scheduledScan */

            // Check quota
            if ($scheduledScan->team_id) {
                if (! $subscriptionService->canScanForTeam($scheduledScan->team)) {
                    return Action::danger("Team quota exceeded for: {$scheduledScan->name}");
                }
            } else {
                if (! $subscriptionService->canScan($scheduledScan->user)) {
                    return Action::danger("User quota exceeded for: {$scheduledScan->name}");
                }
            }

            // Create scan record
            $scan = Scan::create([
                'user_id' => $scheduledScan->user_id,
                'team_id' => $scheduledScan->team_id,
                'scheduled_scan_id' => $scheduledScan->id,
                'url' => $scheduledScan->url,
                'title' => $scheduledScan->name ?? parse_url($scheduledScan->url, PHP_URL_HOST),
                'status' => 'pending',
            ]);

            // Dispatch the job
            ScanWebsiteJob::dispatch($scan);

            // Update last run (but don't update next_run_at since this is manual)
            $scheduledScan->update([
                'last_run_at' => now(),
                'total_runs' => $scheduledScan->total_runs + 1,
            ]);
        }

        $count = $models->count();

        return Action::message($count === 1
            ? 'Scan has been queued successfully!'
            : "{$count} scans have been queued successfully!");
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
