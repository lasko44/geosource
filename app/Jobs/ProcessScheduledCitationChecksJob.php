<?php

namespace App\Jobs;

use App\Models\CitationCheck;
use App\Models\User;
use App\Services\Citation\CitationService;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessScheduledCitationChecksJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * Unique lock duration in seconds.
     * Prevents concurrent job execution.
     */
    public int $uniqueFor = 300;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'process-scheduled-citation-checks';
    }

    public function handle(
        CitationService $citationService,
        SubscriptionService $subscriptionService
    ): void {
        Log::info('Processing scheduled citation checks');

        $queries = $citationService->getQueriesDueForCheck();

        $dispatched = 0;
        $skipped = 0;

        foreach ($queries as $query) {
            $user = $query->user;

            if (! $user) {
                Log::warning('Citation query has no user', ['query_id' => $query->id]);
                $skipped++;

                continue;
            }

            // Get available platforms for user
            $availablePlatforms = $citationService->getAvailablePlatforms($user);

            if (empty($availablePlatforms)) {
                Log::info('User has no available platforms', [
                    'user_id' => $user->id,
                    'query_id' => $query->id,
                ]);
                $skipped++;

                continue;
            }

            // Create and dispatch checks for each available platform with transaction lock
            foreach ($availablePlatforms as $platform) {
                try {
                    $check = DB::transaction(function () use ($user, $query, $platform, $citationService, &$skipped) {
                        // Lock user row to prevent race conditions
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                        // Check quota with locked user
                        if (! $citationService->canPerformCheck($lockedUser)) {
                            Log::info('User reached check limit', [
                                'user_id' => $lockedUser->id,
                                'query_id' => $query->id,
                            ]);
                            $skipped++;

                            return null;
                        }

                        return $citationService->createCheck($query, $platform, $lockedUser);
                    });

                    if ($check) {
                        CheckCitationJob::dispatch($check);
                        $dispatched++;

                        Log::info('Dispatched citation check', [
                            'check_id' => $check->id,
                            'query_id' => $query->id,
                            'platform' => $platform,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch citation check', [
                        'query_id' => $query->id,
                        'platform' => $platform,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Schedule the next check for this query
            $query->scheduleNextCheck();
        }

        Log::info('Completed processing scheduled citation checks', [
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);
    }
}
