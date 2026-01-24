<?php

namespace App\Jobs;

use App\Models\GA4Connection;
use App\Services\Analytics\GA4DataSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGA4DataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct(
        public GA4Connection $ga4Connection
    ) {}

    public function handle(GA4DataSyncService $syncService): void
    {
        Log::info('Starting GA4 data sync', [
            'connection_id' => $this->ga4Connection->id,
            'property_id' => $this->ga4Connection->property_id,
        ]);

        // Mark sync as started
        $this->ga4Connection->markSyncStarted();

        try {
            $result = $syncService->syncConnection($this->ga4Connection);

            if ($result['success']) {
                Log::info('GA4 data sync completed', [
                    'connection_id' => $this->ga4Connection->id,
                    'synced_rows' => $result['synced'],
                    'date_range' => $result['date_range'] ?? null,
                ]);
                // markAsSynced is already called in syncConnection, but status is now set there too
            } else {
                Log::warning('GA4 data sync failed', [
                    'connection_id' => $this->ga4Connection->id,
                    'error' => $result['error'],
                ]);
                $this->ga4Connection->markSyncFailed($result['error']);
            }
        } catch (\Exception $e) {
            Log::error('GA4 data sync exception', [
                'connection_id' => $this->ga4Connection->id,
                'error' => $e->getMessage(),
            ]);

            $this->ga4Connection->markSyncFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('GA4 data sync job failed permanently', [
            'connection_id' => $this->ga4Connection->id,
            'error' => $exception?->getMessage(),
        ]);

        // Optionally deactivate the connection after multiple failures
        if ($this->attempts() >= $this->tries) {
            $this->ga4Connection->deactivate();

            Log::warning('Deactivated GA4 connection due to repeated sync failures', [
                'connection_id' => $this->ga4Connection->id,
            ]);
        }
    }
}
