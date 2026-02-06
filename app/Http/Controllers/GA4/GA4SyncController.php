<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGA4DataJob;
use App\Models\GA4Connection;
use Illuminate\Http\RedirectResponse;

/**
 * Triggers a manual sync of Google Analytics data.
 */
class GA4SyncController extends Controller
{
    /**
     * Trigger a manual sync for a connection.
     */
    public function __invoke(GA4Connection $connection): RedirectResponse
    {
        $this->authorize('sync', $connection);

        if (! $connection->is_active) {
            return back()->withErrors(['sync' => 'This connection is not active.']);
        }

        SyncGA4DataJob::dispatch($connection);

        return back()->with('success', 'Data sync has been started.');
    }
}
