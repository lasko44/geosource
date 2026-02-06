<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Models\GA4Connection;
use Illuminate\Http\JsonResponse;

/**
 * Returns the status of a GA4 data sync operation.
 */
class GA4SyncStatusController extends Controller
{
    /**
     * Get sync status for a connection.
     */
    public function __invoke(GA4Connection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        return response()->json([
            'sync_status' => $connection->sync_status,
            'sync_error' => $connection->sync_error,
            'last_synced_at' => $connection->last_synced_at?->toISOString(),
        ]);
    }
}
