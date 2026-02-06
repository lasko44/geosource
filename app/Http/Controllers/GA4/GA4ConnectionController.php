<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Models\GA4Connection;
use App\Services\Analytics\GA4DataSyncService;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages Google Analytics connections CRUD operations.
 */
class GA4ConnectionController extends Controller
{
    /**
     * Display a listing of GA4 connections.
     */
    public function index(Request $request, GA4Service $ga4Service, GA4DataSyncService $syncService): Response
    {
        $user = $request->user();

        if (! $ga4Service->canAccessGA4($user)) {
            return Inertia::render('Citations/Analytics/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        $team = $ga4Service->getCurrentTeam($user);
        $connections = $ga4Service->getConnectionsForContext($user, $team);
        $trafficData = $ga4Service->buildTrafficData($connections, $syncService);

        return Inertia::render('Citations/Analytics/Index', [
            'connections' => $connections,
            'trafficData' => $trafficData,
            'usage' => $ga4Service->getUsageSummary($user, $team),
            'aiSources' => config('citations.ai_referral_sources'),
            'currentTeam' => $ga4Service->formatTeamData($team),
        ]);
    }

    /**
     * Remove the specified GA4 connection.
     */
    public function destroy(GA4Connection $connection): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $connection);

        $connection->delete();

        return redirect()->route('citations.analytics')
            ->with('success', 'GA4 connection removed successfully.');
    }
}
