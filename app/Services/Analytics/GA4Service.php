<?php

namespace App\Services\Analytics;

use App\Models\GA4Connection;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GA4Service
{
    protected const OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    protected const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    protected const GA4_DATA_API_URL = 'https://analyticsdata.googleapis.com/v1beta';

    protected const GA4_ADMIN_API_URL = 'https://analyticsadmin.googleapis.com/v1beta';

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Check if user can access GA4 features.
     */
    public function canAccessGA4(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $limit = $this->subscriptionService->getLimit($user, 'ga4_connections');

        return $limit !== null && $limit !== 0;
    }

    /**
     * Get maximum GA4 connections allowed.
     */
    public function getMaxConnections(User $user): int
    {
        if ($user->is_admin) {
            return -1;
        }

        return $this->subscriptionService->getLimit($user, 'ga4_connections') ?? 0;
    }

    /**
     * Get current number of connections.
     */
    public function getConnectionsCount(User $user, ?Team $team = null): int
    {
        $query = GA4Connection::where('user_id', $user->id);

        if ($team) {
            $query->where('team_id', $team->id);
        }

        return $query->count();
    }

    /**
     * Check if user can create a new connection.
     */
    public function canCreateConnection(User $user, ?Team $team = null): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $max = $this->getMaxConnections($user);

        if ($max === -1) {
            return true;
        }

        return $this->getConnectionsCount($user, $team) < $max;
    }

    /**
     * Generate the OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $clientId = config('citations.ga4.client_id');
        $redirectUri = config('citations.ga4.redirect_uri');

        // Validate configuration
        if (empty($clientId)) {
            throw new \RuntimeException('Google Client ID is not configured. Please set GOOGLE_CLIENT_ID in your .env file.');
        }

        if (empty($redirectUri)) {
            throw new \RuntimeException('Google redirect URI is not configured. Please ensure APP_URL is set in your .env file.');
        }

        Log::info('GA4 OAuth: Generating authorization URL', [
            'client_id' => substr($clientId, 0, 20).'...',
            'redirect_uri' => $redirectUri,
        ]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', config('citations.ga4.scopes')),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::OAUTH_AUTH_URL.'?'.http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code): array
    {
        $clientId = config('citations.ga4.client_id');
        $clientSecret = config('citations.ga4.client_secret');
        $redirectUri = config('citations.ga4.redirect_uri');

        // Validate configuration
        if (empty($clientSecret)) {
            throw new \RuntimeException('Google Client Secret is not configured. Please set GOOGLE_CLIENT_SECRET in your .env file.');
        }

        $response = Http::asForm()->post(self::OAUTH_TOKEN_URL, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            $body = $response->json();
            $errorDescription = $body['error_description'] ?? $body['error'] ?? 'Unknown error';

            Log::error('GA4 OAuth token exchange failed', [
                'status' => $response->status(),
                'error' => $body['error'] ?? null,
                'error_description' => $errorDescription,
            ]);

            // Provide more helpful error messages
            if (str_contains($errorDescription, 'redirect_uri_mismatch')) {
                throw new \RuntimeException('Redirect URI mismatch. The callback URL configured in Google Cloud Console does not match: '.$redirectUri);
            }

            if (str_contains($errorDescription, 'invalid_client')) {
                throw new \RuntimeException('Invalid client credentials. Please verify your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.');
            }

            throw new \RuntimeException('Failed to exchange authorization code: '.$errorDescription);
        }

        return $response->json();
    }

    /**
     * Refresh an access token.
     */
    public function refreshToken(GA4Connection $connection): array
    {
        $response = Http::asForm()->post(self::OAUTH_TOKEN_URL, [
            'client_id' => config('citations.ga4.client_id'),
            'client_secret' => config('citations.ga4.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            Log::error('GA4 token refresh failed', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Deactivate the connection if refresh fails
            $connection->deactivate();

            throw new \RuntimeException('Failed to refresh access token');
        }

        $data = $response->json();

        // Update the connection with new tokens
        $connection->updateTokens(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            $data['expires_in']
        );

        return $data;
    }

    /**
     * Get a valid access token (refreshing if needed).
     */
    public function getValidAccessToken(GA4Connection $connection): string
    {
        if ($connection->isTokenExpired()) {
            $this->refreshToken($connection);
            $connection->refresh();
        }

        return $connection->access_token;
    }

    /**
     * List available GA4 properties for the connected account.
     */
    public function listProperties(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::GA4_ADMIN_API_URL.'/accountSummaries');

        if (! $response->successful()) {
            $body = $response->json();
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            $errorStatus = $body['error']['status'] ?? null;

            Log::error('GA4 list properties failed', [
                'status' => $response->status(),
                'error_status' => $errorStatus,
                'error_message' => $errorMessage,
            ]);

            // Provide helpful error messages
            if ($response->status() === 403) {
                if (str_contains($errorMessage, 'not been used') || str_contains($errorMessage, 'disabled')) {
                    throw new \RuntimeException('The Google Analytics Admin API is not enabled. Please enable it in your Google Cloud Console: https://console.cloud.google.com/apis/library/analyticsadmin.googleapis.com');
                }
                throw new \RuntimeException('Access denied to Google Analytics. Please make sure you have access to at least one GA4 property.');
            }

            if ($response->status() === 401) {
                throw new \RuntimeException('Authentication failed. Please try connecting again.');
            }

            throw new \RuntimeException('Failed to list GA4 properties: '.$errorMessage);
        }

        $data = $response->json();
        $properties = [];

        foreach ($data['accountSummaries'] ?? [] as $account) {
            foreach ($account['propertySummaries'] ?? [] as $property) {
                $properties[] = [
                    'account_id' => $account['account'],
                    'account_name' => $account['displayName'],
                    'property_id' => $property['property'],
                    'property_name' => $property['displayName'],
                ];
            }
        }

        return $properties;
    }

    /**
     * Create a new GA4 connection.
     */
    public function createConnection(
        User $user,
        ?Team $team,
        string $googleAccountId,
        string $propertyId,
        string $propertyName,
        string $accessToken,
        string $refreshToken,
        int $expiresIn
    ): GA4Connection {
        return GA4Connection::create([
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'google_account_id' => $googleAccountId,
            'property_id' => $propertyId,
            'property_name' => $propertyName,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => now()->addSeconds($expiresIn),
            'is_active' => true,
        ]);
    }

    /**
     * Run a GA4 report query.
     */
    public function runReport(GA4Connection $connection, array $request): array
    {
        $accessToken = $this->getValidAccessToken($connection);

        // Extract property ID number from the full path
        $propertyId = str_replace('properties/', '', $connection->property_id);

        $response = Http::withToken($accessToken)
            ->post(self::GA4_DATA_API_URL."/properties/{$propertyId}:runReport", $request);

        if (! $response->successful()) {
            Log::error('GA4 report query failed', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Failed to run GA4 report');
        }

        return $response->json();
    }

    /**
     * Get referral traffic data.
     */
    public function getReferralData(GA4Connection $connection, string $startDate, string $endDate): array
    {
        $request = [
            'dateRanges' => [
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ],
            ],
            'dimensions' => [
                ['name' => 'date'],
                ['name' => 'sessionSource'],
                ['name' => 'sessionMedium'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'engagedSessions'],
                ['name' => 'bounceRate'],
                ['name' => 'averageSessionDuration'],
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'sessionMedium',
                    'stringFilter' => [
                        'value' => 'referral',
                    ],
                ],
            ],
            'orderBys' => [
                [
                    'dimension' => [
                        'dimensionName' => 'date',
                    ],
                    'desc' => true,
                ],
            ],
        ];

        return $this->runReport($connection, $request);
    }

    /**
     * Get AI platform referral data.
     */
    public function getAIReferralData(GA4Connection $connection, string $startDate, string $endDate): array
    {
        $aiSources = config('citations.ai_referral_sources', []);

        if (empty($aiSources)) {
            return ['rows' => []];
        }

        $request = [
            'dateRanges' => [
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ],
            ],
            'dimensions' => [
                ['name' => 'date'],
                ['name' => 'sessionSource'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'engagedSessions'],
                ['name' => 'bounceRate'],
                ['name' => 'averageSessionDuration'],
            ],
            'dimensionFilter' => [
                'orGroup' => [
                    'expressions' => array_map(fn ($source) => [
                        'filter' => [
                            'fieldName' => 'sessionSource',
                            'stringFilter' => [
                                'matchType' => 'CONTAINS',
                                'value' => $source,
                                'caseSensitive' => false,
                            ],
                        ],
                    ], $aiSources),
                ],
            ],
            'orderBys' => [
                [
                    'dimension' => [
                        'dimensionName' => 'date',
                    ],
                    'desc' => true,
                ],
            ],
        ];

        return $this->runReport($connection, $request);
    }

    /**
     * Get usage summary for GA4.
     */
    public function getUsageSummary(User $user, ?Team $team = null): array
    {
        $max = $this->getMaxConnections($user);
        $count = $this->getConnectionsCount($user, $team);

        return [
            'can_access' => $this->canAccessGA4($user),
            'connections_count' => $count,
            'connections_limit' => $max,
            'connections_remaining' => $max === -1 ? -1 : max(0, $max - $count),
            'connections_is_unlimited' => $max === -1,
            'can_create_connection' => $this->canCreateConnection($user, $team),
        ];
    }
}
