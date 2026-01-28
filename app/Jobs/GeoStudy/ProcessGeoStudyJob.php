<?php

namespace App\Jobs\GeoStudy;

use App\Models\GeoStudy;
use App\Models\GeoStudyResult;
use App\Services\GeoStudy\GeoStudyService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessGeoStudyJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200; // 2 hours max for batch setup

    public function __construct(
        public GeoStudy $study
    ) {
        $this->onQueue('geo-study');
    }

    public function handle(GeoStudyService $studyService): void
    {
        // Check if study was cancelled
        $this->study->refresh();
        if ($this->study->isCancelled()) {
            Log::info("GEO Study {$this->study->id} was cancelled");
            return;
        }

        // Get all pending results
        $pendingResults = $this->study->results()
            ->where('status', GeoStudyResult::STATUS_PENDING)
            ->get();

        if ($pendingResults->isEmpty()) {
            Log::info("No pending results for GEO Study {$this->study->id}");
            $studyService->complete($this->study);
            return;
        }

        // Create jobs for each URL
        $jobs = $pendingResults->map(function (GeoStudyResult $result) {
            return new ProcessGeoStudyUrlJob($this->study, $result);
        })->toArray();

        Log::info("Creating batch for GEO Study {$this->study->id} with " . count($jobs) . " jobs");

        // Create batch
        $batch = Bus::batch($jobs)
            ->name("GEO Study: {$this->study->name}")
            ->allowFailures()
            ->onQueue('geo-study')
            ->progress(function ($batch) {
                $this->updateStudyProgress($batch);
            })
            ->then(function ($batch) use ($studyService) {
                Log::info("GEO Study batch completed: {$batch->id}");
                $this->study->refresh();

                if (! $this->study->isCancelled()) {
                    $studyService->complete($this->study);
                }
            })
            ->catch(function ($batch, $e) use ($studyService) {
                Log::error("GEO Study batch failed: {$batch->id}", ['error' => $e->getMessage()]);
                $this->study->refresh();

                if (! $this->study->isCancelled() && $batch->failedJobs >= $batch->totalJobs) {
                    $studyService->fail($this->study, 'All jobs in batch failed: ' . $e->getMessage());
                }
            })
            ->finally(function ($batch) {
                Log::info("GEO Study batch finished: {$batch->id}");
            })
            ->dispatch();

        // Store batch ID for tracking
        $this->study->update(['batch_id' => $batch->id]);

        Log::info("GEO Study {$this->study->id} batch dispatched: {$batch->id}");
    }

    /**
     * Update study progress based on batch status.
     */
    private function updateStudyProgress($batch): void
    {
        $this->study->refresh();

        if ($this->study->isCancelled()) {
            return;
        }

        $this->study->updateProgress();
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("ProcessGeoStudyJob failed for study {$this->study->id}", [
            'error' => $exception?->getMessage(),
        ]);

        $this->study->update([
            'status' => GeoStudy::STATUS_FAILED,
            'error_message' => 'Failed to start batch processing: ' . ($exception?->getMessage() ?? 'Unknown error'),
            'completed_at' => now(),
        ]);
    }
}
