<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Citation\CitationService;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Processes all scheduled citation checks that are due to run.
 */
class ProcessScheduledCitationChecksJob implements ShouldBeUnique, ShouldQueue
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
        SubscriptionService $subscriptionService,
        TokenService $tokenService
    ): void {
        Log::info('Processing scheduled citation checks');

        $queries = $citationService->getQueriesDueForCheck();

        $dispatched = 0;
        $skipped = 0;
        $budgetExceeded = 0;

        foreach ($queries as $query) {
            $user = $query->user;

            if (! $user) {
                Log::warning('Citation query has no user', ['query_id' => $query->id]);
                $skipped++;

                continue;
            }

            // Check if query can afford a scheduled run (budget limit)
            if (! $query->canAffordScheduledRun()) {
                Log::info('Query exceeded monthly token budget', [
                    'query_id' => $query->id,
                    'user_id' => $user->id,
                    'budget' => $query->monthly_token_budget,
                    'spent' => $query->tokens_spent_this_month,
                ]);
                $budgetExceeded++;
                // Still schedule next check - budget may reset or user may add budget
                $query->scheduleNextCheck();

                continue;
            }

            // Get platforms to check (user-selected or defaults)
            $platformsToCheck = $query->getScheduledPlatformsToCheck();

            // Filter to only platforms user has access to
            $availablePlatforms = $citationService->getAvailablePlatforms($user);
            $platformsToCheck = array_intersect($platformsToCheck, $availablePlatforms);

            if (empty($platformsToCheck)) {
                Log::info('No platforms available for scheduled check', [
                    'user_id' => $user->id,
                    'query_id' => $query->id,
                ]);
                $skipped++;
                $query->scheduleNextCheck();

                continue;
            }

            $tokensSpentThisRun = 0;
            $delay = 0;

            // Create and dispatch checks for each platform with transaction lock
            foreach ($platformsToCheck as $platform) {
                try {
                    $result = DB::transaction(function () use ($user, $query, $platform, $citationService, $tokenService, &$skipped) {
                        // Lock user row to prevent race conditions
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                        // Get token cost for this platform
                        $tokenCost = $citationService->getTokenCost($platform);

                        // Check if user has enough tokens
                        if (! $lockedUser->is_admin && $tokenCost > 0 && ($lockedUser->token_balance ?? 0) < $tokenCost) {
                            Log::info('User has insufficient tokens for scheduled check', [
                                'user_id' => $lockedUser->id,
                                'query_id' => $query->id,
                                'platform' => $platform,
                                'cost' => $tokenCost,
                                'balance' => $lockedUser->token_balance,
                            ]);
                            $skipped++;

                            return null;
                        }

                        // Deduct tokens for non-admins
                        if (! $lockedUser->is_admin && $tokenCost > 0) {
                            $providerKey = config("tokens.citation_providers.{$platform}");
                            $tokenService->spend($lockedUser, $providerKey, [
                                'query_id' => $query->id,
                                'platform' => $platform,
                                'scheduled' => true,
                            ]);
                        }

                        $check = $citationService->createCheck($query, $platform, $lockedUser);

                        return [
                            'check' => $check,
                            'cost' => $tokenCost,
                        ];
                    });

                    if ($result && $result['check']) {
                        // Dispatch with staggered delay to avoid rate limits
                        CheckCitationJob::dispatch($result['check'])->delay(now()->addSeconds($delay));
                        $delay += 5; // 5 second delay between each platform
                        $dispatched++;
                        $tokensSpentThisRun += $result['cost'];

                        Log::info('Dispatched scheduled citation check', [
                            'check_id' => $result['check']->id,
                            'query_id' => $query->id,
                            'platform' => $platform,
                            'tokens_charged' => $result['cost'],
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch scheduled citation check', [
                        'query_id' => $query->id,
                        'platform' => $platform,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Record tokens spent for budget tracking
            if ($tokensSpentThisRun > 0) {
                $query->recordTokensSpent($tokensSpentThisRun);
            }

            // Schedule the next check for this query
            $query->scheduleNextCheck();
        }

        Log::info('Completed processing scheduled citation checks', [
            'dispatched' => $dispatched,
            'skipped' => $skipped,
            'budget_exceeded' => $budgetExceeded,
        ]);
    }
}
