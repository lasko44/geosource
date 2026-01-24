<?php

namespace App\Console\Commands;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
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

        foreach ($dueScans as $scheduledScan) {
            try {
                // Check if user still has the scheduled_scans feature
                if (! $scheduledScan->user->hasFeature('scheduled_scans')) {
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
                    if (! $this->subscriptionService->canScan($scheduledScan->user)) {
                        $this->warn("Skipping scan {$scheduledScan->uuid}: Personal quota exceeded.");
                        $scheduledScan->markAsRun(); // Still mark as run to advance next_run_at
                        $skipCount++;

                        continue;
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

                // Update scheduled scan stats
                $scheduledScan->markAsRun();

                $this->info("Dispatched scan for: {$scheduledScan->url}");
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
