<?php

namespace App\Http\Controllers;

use App\Jobs\SyncGA4DataJob;
use App\Models\GA4Connection;
use App\Services\Analytics\GA4DataSyncService;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GA4Controller extends Controller
{
    public function __construct(
        private GA4Service $ga4Service,
        private GA4DataSyncService $syncService,
    ) {}

    /**
     * Display the GA4 analytics dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check access
        if (! $this->ga4Service->canAccessGA4($user)) {
            return Inertia::render('Citations/Analytics/Upgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        // Get connections
        $connectionsQuery = GA4Connection::where('user_id', $user->id);
        if ($team) {
            $connectionsQuery->where('team_id', $team->id);
        } else {
            $connectionsQuery->whereNull('team_id');
        }

        $connections = $connectionsQuery->get();

        // Get usage summary
        $usage = $this->ga4Service->getUsageSummary($user, $team);

        // Get AI traffic data for active connections
        $trafficData = [];
        foreach ($connections->where('is_active', true) as $connection) {
            $trafficData[$connection->id] = [
                'summary' => $connection->getAITrafficSummary(30),
                'trend' => $this->syncService->getDailyAITrafficTrend($connection, 30),
            ];
        }

        return Inertia::render('Citations/Analytics/Index', [
            'connections' => $connections,
            'trafficData' => $trafficData,
            'usage' => $usage,
            'aiSources' => config('citations.ai_referral_sources'),
            'currentTeam' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
            ] : null,
        ]);
    }

    /**
     * Start the OAuth flow to connect a GA4 property.
     */
    public function connect(Request $request)
    {
        $user = $request->user();

        // Check if user can create connections
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        if (! $this->ga4Service->canCreateConnection($user, $team)) {
            return back()->withErrors([
                'limit' => 'You have reached your GA4 connection limit. Please upgrade your plan.',
            ]);
        }

        // Generate state token
        $state = Str::random(40);
        session(['ga4_oauth_state' => $state]);

        // Redirect to Google OAuth
        return redirect($this->ga4Service->getAuthorizationUrl($state));
    }

    /**
     * Handle the OAuth callback.
     */
    public function callback(Request $request)
    {
        $user = $request->user();

        // Check if user is authenticated (session might be lost during OAuth redirect)
        if (! $user) {
            Log::warning('GA4 OAuth callback: User not authenticated', [
                'has_code' => $request->has('code'),
                'has_state' => $request->has('state'),
            ]);

            return redirect()->route('login')
                ->withErrors(['oauth' => 'Your session expired. Please log in and try connecting GA4 again.']);
        }

        // Verify state
        $state = session('ga4_oauth_state');
        if (! $state || $request->state !== $state) {
            Log::warning('GA4 OAuth state mismatch', [
                'session_state' => $state,
                'request_state' => $request->state,
            ]);

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'Invalid OAuth state. Please try again.']);
        }
        session()->forget('ga4_oauth_state');

        // Check for error from Google
        if ($request->has('error')) {
            Log::warning('GA4 OAuth error from Google', [
                'error' => $request->error,
                'description' => $request->error_description ?? null,
            ]);

            $errorMessage = 'Authorization was denied or an error occurred.';
            if ($request->error === 'access_denied') {
                $errorMessage = 'You denied access to Google Analytics. Please try again and allow access.';
            }

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => $errorMessage]);
        }

        // Check for authorization code
        if (! $request->has('code')) {
            Log::warning('GA4 OAuth: No authorization code received');

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'No authorization code received from Google. Please try again.']);
        }

        try {
            // Exchange code for tokens
            $tokens = $this->ga4Service->exchangeCode($request->code);

            // Validate tokens
            if (empty($tokens['access_token'])) {
                Log::error('GA4 OAuth: Empty access token received');

                return redirect()->route('citations.analytics')
                    ->withErrors(['oauth' => 'Failed to get access token from Google. Please try again.']);
            }

            // Store tokens temporarily in session for property selection
            session([
                'ga4_tokens' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'expires_in' => $tokens['expires_in'] ?? 3600,
                ],
            ]);

            // Get available properties
            $properties = $this->ga4Service->listProperties($tokens['access_token']);

            if (empty($properties)) {
                session()->forget('ga4_tokens');
                Log::info('GA4 OAuth: No properties found for user', ['user_id' => $user->id]);

                return redirect()->route('citations.analytics')
                    ->withErrors(['oauth' => 'No GA4 properties found in your Google account. Make sure you have at least one GA4 property set up.']);
            }

            Log::info('GA4 OAuth: Found properties', [
                'user_id' => $user->id,
                'count' => count($properties),
            ]);

            // Redirect to property selection if multiple properties
            if (count($properties) > 1) {
                return Inertia::render('Citations/Analytics/SelectProperty', [
                    'properties' => $properties,
                ]);
            }

            // Auto-select if only one property
            return $this->createConnectionFromProperty($user, $properties[0]);

        } catch (\Exception $e) {
            Log::error('GA4 OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'Failed to connect to Google Analytics: '.$e->getMessage()]);
        }
    }

    /**
     * Select a GA4 property to connect.
     */
    public function selectProperty(Request $request)
    {
        $request->validate([
            'property_id' => 'required|string',
            'property_name' => 'required|string',
            'account_id' => 'required|string',
        ]);

        $user = $request->user();

        // Get tokens from session
        $tokens = session('ga4_tokens');
        if (! $tokens) {
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'Session expired. Please try connecting again.']);
        }

        return $this->createConnectionFromProperty($user, [
            'property_id' => $request->property_id,
            'property_name' => $request->property_name,
            'account_id' => $request->account_id,
        ]);
    }

    /**
     * Create a connection from a selected property.
     */
    private function createConnectionFromProperty($user, array $property)
    {
        $tokens = session('ga4_tokens');
        session()->forget('ga4_tokens');

        // Get team context
        $currentTeamId = session('current_team_id');
        $team = null;
        if ($currentTeamId && $currentTeamId !== 'personal') {
            $team = $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        // Check for existing connection with same property
        $existingQuery = GA4Connection::where('property_id', $property['property_id']);
        if ($team) {
            $existingQuery->where('team_id', $team->id);
        } else {
            $existingQuery->where('user_id', $user->id)->whereNull('team_id');
        }

        if ($existingQuery->exists()) {
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'This GA4 property is already connected.']);
        }

        try {
            // Create the connection
            $connection = $this->ga4Service->createConnection(
                $user,
                $team,
                $property['account_id'],
                $property['property_id'],
                $property['property_name'],
                $tokens['access_token'],
                $tokens['refresh_token'],
                $tokens['expires_in']
            );

            // Trigger initial sync
            SyncGA4DataJob::dispatch($connection);

            return redirect()->route('citations.analytics')
                ->with('success', "Connected to {$property['property_name']} successfully. Initial data sync has started.");

        } catch (\Exception $e) {
            Log::error('Failed to create GA4 connection', [
                'error' => $e->getMessage(),
                'property' => $property,
            ]);

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'Failed to save connection. Please try again.']);
        }
    }

    /**
     * Get AI referral data for a connection.
     */
    public function referrals(GA4Connection $connection, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($connection->user_id !== $user->id) {
            if ($connection->team_id && ! $user->allTeams()->contains('id', $connection->team_id)) {
                abort(403);
            }
        }

        $days = (int) $request->input('days', 30);
        $days = min(max($days, 7), 90);

        $data = $connection->getAIReferralData($days);

        return response()->json([
            'data' => $data,
            'summary' => $connection->getAITrafficSummary($days),
        ]);
    }

    /**
     * Get AI traffic summary for a connection.
     */
    public function aiTraffic(GA4Connection $connection, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($connection->user_id !== $user->id) {
            if ($connection->team_id && ! $user->allTeams()->contains('id', $connection->team_id)) {
                abort(403);
            }
        }

        $days = (int) $request->input('days', 30);
        $days = min(max($days, 7), 90);

        return response()->json([
            'summary' => $connection->getAITrafficSummary($days),
            'trend' => $this->syncService->getDailyAITrafficTrend($connection, $days),
        ]);
    }

    /**
     * Trigger a manual sync for a connection.
     */
    public function sync(GA4Connection $connection, Request $request)
    {
        $user = $request->user();

        // Authorization - check both user ownership and team membership
        if ($connection->user_id !== $user->id) {
            if (! $connection->team_id || ! $user->allTeams()->contains('id', $connection->team_id)) {
                abort(403);
            }
        }

        if (! $connection->is_active) {
            return back()->withErrors(['sync' => 'This connection is not active.']);
        }

        // Dispatch sync job
        SyncGA4DataJob::dispatch($connection);

        return back()->with('success', 'Data sync has been started.');
    }

    /**
     * Get sync status for a connection.
     */
    public function syncStatus(GA4Connection $connection, Request $request)
    {
        $user = $request->user();

        // Authorization
        if ($connection->user_id !== $user->id) {
            if (! $connection->team_id || ! $user->allTeams()->contains('id', $connection->team_id)) {
                abort(403);
            }
        }

        return response()->json([
            'sync_status' => $connection->sync_status,
            'sync_error' => $connection->sync_error,
            'last_synced_at' => $connection->last_synced_at?->toISOString(),
        ]);
    }

    /**
     * Disconnect (delete) a GA4 connection.
     */
    public function disconnect(GA4Connection $connection, Request $request)
    {
        $user = $request->user();

        // Authorization - check both user ownership and team membership
        // Only the owner can disconnect, team members cannot
        if ($connection->user_id !== $user->id) {
            abort(403, 'Only the connection owner can disconnect.');
        }

        $connection->delete();

        return redirect()->route('citations.analytics')
            ->with('success', 'GA4 connection removed successfully.');
    }
}
