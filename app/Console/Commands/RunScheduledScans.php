<?php

namespace App\Console\Commands;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunScheduledScans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scans:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all scheduled scans that are due';

    public function __construct(
        private SubscriptionService $subscriptionService,
        private TokenService $tokenService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dueScans = ScheduledScan::due()->with(['user', 'team'])->get();

        if ($dueScans->isEmpty()) {
            $this->info('No scheduled scans are due.');

            return Command::SUCCESS;
        }

        $this->info("Found {$dueScans->count()} scheduled scan(s) due to run.");

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        $tokenCost = config('tokens.costs.scheduled_scan', 5);

        foreach ($dueScans as $scheduledScan) {
            try {
                $user = $scheduledScan->user;

                // Check if user still has the scheduled_scans feature
                if (! $user->hasFeature('scheduled_scans')) {
                    $this->warn("Skipping scan {$scheduledScan->uuid}: User no longer has scheduled_scans feature.");
                    $scheduledScan->is_active = false;
                    $scheduledScan->save();
                    $skipCount++;

                    continue;
                }

                // Check quota
                if ($scheduledScan->team_id) {
                    if (! $this->subscriptionService->canScanForTeam($scheduledScan->team)) {
                        $this->warn("Skipping scan {$scheduledScan->uuid}: Team quota exceeded.");
                        $scheduledScan->markAsRun(); // Still mark as run to advance next_run_at
                        $skipCount++;

                        continue;
                    }
                } else {
                    if (! $this->subscriptionService->canScan($user)) {
                        $this->warn("Skipping scan {$scheduledScan->uuid}: Personal quota exceeded.");
                        $scheduledScan->markAsRun(); // Still mark as run to advance next_run_at
                        $skipCount++;

                        continue;
                    }
                }

                // Check if user has enough tokens (admins skip token check)
                if (! $user->is_admin && $tokenCost > 0 && ($user->token_balance ?? 0) < $tokenCost) {
                    $this->warn("Skipping scan {$scheduledScan->uuid}: Insufficient tokens ({$user->token_balance} < {$tokenCost}).");
                    $scheduledScan->markAsRun(); // Still mark as run to advance next_run_at
                    $skipCount++;

                    continue;
                }

                // Use transaction with pessimistic locking to deduct tokens and create scan
                $scan = DB::transaction(function () use ($scheduledScan, $user, $tokenCost) {
                    $tokensCharged = false;
                    $tokensAmount = 0;

                    // Deduct tokens if not admin
                    if (! $user->is_admin && $tokenCost > 0) {
                        // Re-lock user to verify balance
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                        if (($lockedUser->token_balance ?? 0) < $tokenCost) {
                            throw new \Exception('Insufficient tokens after lock verification.');
                        }

                        $this->tokenService->spend($lockedUser, 'scheduled_scan', [
                            'scheduled_scan_id' => $scheduledScan->id,
                            'url' => $scheduledScan->url,
                        ]);

                        $tokensCharged = true;
                        $tokensAmount = $tokenCost;
                    }

                    // Create scan record with token tracking
                    return Scan::create([
                        'user_id' => $scheduledScan->user_id,
                        'team_id' => $scheduledScan->team_id,
                        'scheduled_scan_id' => $scheduledScan->id,
                        'url' => $scheduledScan->url,
                        'title' => $scheduledScan->name ?? parse_url($scheduledScan->url, PHP_URL_HOST),
                        'status' => 'pending',
                        'tokens_charged' => $tokensCharged,
                        'tokens_amount' => $tokensAmount,
                    ]);
                });

                // Dispatch the job
                ScanWebsiteJob::dispatch($scan);

                // Update scheduled scan stats
                $scheduledScan->markAsRun();

                $this->info("Dispatched scan for: {$scheduledScan->url} (charged {$tokenCost} tokens)");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("Error processing scheduled scan {$scheduledScan->uuid}: {$e->getMessage()}");
                Log::error('Scheduled scan error', [
                    'scheduled_scan_id' => $scheduledScan->id,
                    'error' => $e->getMessage(),
                ]);
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$successCount} dispatched, {$skipCount} skipped, {$errorCount} errors.");

        return Command::SUCCESS;
    }
}
