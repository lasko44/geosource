<?php

namespace App\Services\Citation;

use App\Models\CitationAlert;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Jobs\CheckCitationJob;
use App\Services\Analytics\GA4Service;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CitationService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected GA4Service $ga4Service,
        protected TokenService $tokenService,
    ) {}

    /**
     * Check if user can access citation features.
     * Token-based: any user with tokens can access citations.
     */
    public function canAccessCitations(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Token-based access - any user with tokens can access
        return ($user->token_balance ?? 0) > 0;
    }

    /**
     * Get available platforms for the user.
     * Token-based: all platforms available to users with tokens.
     */
    public function getAvailablePlatforms(User $user): array
    {
        // All supported platforms for citation checking
        $supportedPlatforms = [
            'perplexity', 'claude', 'openai', 'gemini', 'deepseek',
            'google', 'youtube', 'facebook',
        ];

        // Admins and users with tokens get all platforms
        if ($user->is_admin || ($user->token_balance ?? 0) > 0) {
            return $supportedPlatforms;
        }

        return [];
    }

    /**
     * Get the token cost for a citation check on a specific platform.
     */
    public function getTokenCost(string $platform): int
    {
        $providerKey = config("tokens.citation_providers.{$platform}");
        if (!$providerKey) {
            return 0;
        }

        return config("tokens.costs.{$providerKey}", 0);
    }

    /**
     * Check if user can afford a citation check on a platform.
     */
    public function canAffordCheck(User $user, string $platform): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $cost = $this->getTokenCost($platform);
        return ($user->token_balance ?? 0) >= $cost;
    }

    /**
     * Get available frequencies for the user.
     * Token-based: all frequencies available (scheduled checks cost tokens per run).
     */
    public function getAvailableFrequencies(User $user): array
    {
        // Admins and users with tokens get all frequencies
        if ($user->is_admin || ($user->token_balance ?? 0) > 0) {
            return [
                CitationQuery::FREQUENCY_MANUAL,
                CitationQuery::FREQUENCY_DAILY,
                CitationQuery::FREQUENCY_WEEKLY,
            ];
        }

        return [];
    }

    /**
     * Get the maximum number of queries allowed.
     * Token-based: unlimited queries (users pay per check, not per query).
     */
    public function getMaxQueries(User $user): int
    {
        // Admins and users with tokens get unlimited queries
        if ($user->is_admin || ($user->token_balance ?? 0) > 0) {
            return -1;
        }

        return 0;
    }

    /**
     * Get the number of queries created by the user.
     */
    public function getQueriesCreated(User $user, ?Team $team = null): int
    {
        $query = CitationQuery::where('user_id', $user->id);

        if ($team) {
            $query->where('team_id', $team->id);
        } else {
            $query->whereNull('team_id');
        }

        return $query->count();
    }

    /**
     * Get remaining query slots.
     */
    public function getQueriesRemaining(User $user, ?Team $team = null): int
    {
        $max = $this->getMaxQueries($user);

        if ($max === -1) {
            return -1;
        }

        $created = $this->getQueriesCreated($user, $team);

        return max(0, $max - $created);
    }

    /**
     * Check if user can create a new query.
     */
    public function canCreateQuery(User $user, ?Team $team = null): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $remaining = $this->getQueriesRemaining($user, $team);

        return $remaining === -1 || $remaining > 0;
    }

    /**
     * Get the daily check limit for the user.
     * Token-based: unlimited checks (users pay per check).
     */
    public function getDailyCheckLimit(User $user): int
    {
        // Admins and users with tokens get unlimited daily checks
        if ($user->is_admin || ($user->token_balance ?? 0) > 0) {
            return -1;
        }

        return 0;
    }

    /**
     * Get checks performed today.
     * Uses withTrashed() to prevent quota bypass via soft-delete exploitation.
     */
    public function getChecksPerformedToday(User $user): int
    {
        $timezone = $user->timezone ?? 'UTC';
        $startOfDay = Carbon::now($timezone)->startOfDay()->utc();

        return CitationCheck::withTrashed()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startOfDay)
            ->count();
    }

    /**
     * Get remaining checks for today.
     */
    public function getChecksRemainingToday(User $user): int
    {
        $limit = $this->getDailyCheckLimit($user);

        if ($limit === -1) {
            return -1;
        }

        $used = $this->getChecksPerformedToday($user);

        return max(0, $limit - $used);
    }

    /**
     * Check if user can perform a check (has tokens).
     */
    public function canPerformCheck(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Token-based: user needs at least some tokens
        return ($user->token_balance ?? 0) > 0;
    }

    /**
     * Create a new citation query.
     */
    public function createQuery(
        User $user,
        string $query,
        string $domain,
        ?string $brand = null,
        string $frequency = CitationQuery::FREQUENCY_MANUAL,
        ?Team $team = null,
        array $scheduledPlatforms = [],
        ?int $monthlyTokenBudget = null
    ): CitationQuery {
        $nextCheck = match ($frequency) {
            CitationQuery::FREQUENCY_DAILY => now()->addDay(),
            CitationQuery::FREQUENCY_WEEKLY => now()->addWeek(),
            default => null,
        };

        return CitationQuery::create([
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'query' => $query,
            'domain' => $domain,
            'brand' => $brand,
            'frequency' => $frequency,
            'scheduled_platforms' => ! empty($scheduledPlatforms) ? $scheduledPlatforms : null,
            'monthly_token_budget' => $monthlyTokenBudget,
            'next_check_at' => $nextCheck,
            'is_active' => true,
        ]);
    }

    /**
     * Create a citation check record.
     */
    public function createCheck(
        CitationQuery $citationQuery,
        string $platform,
        User $user
    ): CitationCheck {
        return CitationCheck::create([
            'citation_query_id' => $citationQuery->id,
            'user_id' => $user->id,
            'team_id' => $citationQuery->team_id,
            'platform' => $platform,
            'status' => CitationCheck::STATUS_PENDING,
        ]);
    }

    /**
     * Execute a single citation check with token deduction.
     */
    public function executeCheck(CitationQuery $query, string $platform, User $user): CitationCheck
    {
        $tokenCost = $this->getTokenCost($platform);

        $check = DB::transaction(function () use ($user, $query, $platform, $tokenCost) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (! $lockedUser->is_admin && $tokenCost > 0 && ($lockedUser->token_balance ?? 0) < $tokenCost) {
                throw new \RuntimeException("Insufficient tokens. You need {$tokenCost} tokens.");
            }

            if (! $lockedUser->is_admin && $tokenCost > 0) {
                $providerKey = config("tokens.citation_providers.{$platform}");
                $this->tokenService->spend($lockedUser, $providerKey, [
                    'query_id' => $query->id,
                    'platform' => $platform,
                ]);
            }

            return $this->createCheck($query, $platform, $lockedUser);
        });

        CheckCitationJob::dispatch($check);

        return $check;
    }

    /**
     * Execute bulk citation checks with token deduction.
     *
     * @return CitationCheck[]
     */
    public function executeBulkChecks(CitationQuery $query, array $platforms, User $user): array
    {
        $totalCost = 0;
        foreach ($platforms as $platform) {
            $totalCost += $this->getTokenCost($platform);
        }

        $checks = DB::transaction(function () use ($user, $query, $platforms, $totalCost) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (! $lockedUser->is_admin && $totalCost > 0 && ($lockedUser->token_balance ?? 0) < $totalCost) {
                throw new \RuntimeException("Insufficient tokens. You need {$totalCost} tokens.");
            }

            $createdChecks = [];
            foreach ($platforms as $platform) {
                $tokenCost = $this->getTokenCost($platform);

                if (! $lockedUser->is_admin && $tokenCost > 0) {
                    $providerKey = config("tokens.citation_providers.{$platform}");
                    $this->tokenService->spend($lockedUser, $providerKey, [
                        'query_id' => $query->id,
                        'platform' => $platform,
                    ]);
                    $lockedUser->refresh();
                }

                $createdChecks[] = $this->createCheck($query, $platform, $lockedUser);
            }

            return $createdChecks;
        });

        foreach ($checks as $index => $check) {
            $delay = $index * 5;
            CheckCitationJob::dispatch($check)->delay(now()->addSeconds($delay));
        }

        return $checks;
    }

    /**
     * Build trend data for a query's checks, grouped by date and platform.
     */
    public function buildQueryTrendData(CitationQuery $query): array
    {
        $checks = CitationCheck::where('citation_query_id', $query->id)
            ->where('status', CitationCheck::STATUS_COMPLETED)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($checks->isEmpty()) {
            return ['dates' => [], 'platforms' => [], 'totals' => ['cited' => [], 'checks' => []]];
        }

        $platformConfig = config('citations.platforms');
        $byDate = $checks->groupBy(fn ($check) => $check->created_at->format('Y-m-d'));
        $dates = $byDate->keys()->sort()->values()->all();
        $activePlatforms = $checks->pluck('platform')->unique()->values()->all();

        $platforms = [];
        foreach ($activePlatforms as $platform) {
            $citations = [];
            $checkCounts = [];
            foreach ($dates as $date) {
                $dayChecks = $byDate[$date]->where('platform', $platform);
                $citations[] = $dayChecks->where('is_cited', true)->count();
                $checkCounts[] = $dayChecks->count();
            }
            $platforms[$platform] = [
                'name' => $platformConfig[$platform]['name'] ?? $platform,
                'color' => $platformConfig[$platform]['color'] ?? '#6B7280',
                'citations' => $citations,
                'checks' => $checkCounts,
            ];
        }

        $totalCited = [];
        $totalChecks = [];
        foreach ($dates as $date) {
            $dayChecks = $byDate[$date];
            $totalCited[] = $dayChecks->where('is_cited', true)->count();
            $totalChecks[] = $dayChecks->count();
        }

        return [
            'dates' => $dates,
            'platforms' => $platforms,
            'totals' => [
                'cited' => $totalCited,
                'checks' => $totalChecks,
            ],
        ];
    }

    /**
     * Get the current team from session.
     */
    public function getCurrentTeam(User $user): ?Team
    {
        $currentTeamId = session('current_team_id');

        if ($currentTeamId && $currentTeamId !== 'personal') {
            return $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        return null;
    }

    /**
     * Get queries for user filtered by team context.
     */
    public function getQueriesForUser(User $user, ?Team $team = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CitationQuery::where('user_id', $user->id);

        if ($team) {
            $query->where('team_id', $team->id);
        } else {
            $query->whereNull('team_id');
        }

        return $query
            ->with(['checks' => function ($q) {
                $q->where('status', CitationCheck::STATUS_COMPLETED)
                    ->orderBy('created_at', 'desc')
                    ->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent checks for user filtered by team context.
     */
    public function getRecentChecks(User $user, ?Team $team = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return CitationCheck::where('user_id', $user->id)
            ->when($team, fn ($q) => $q->where('team_id', $team->id))
            ->when(! $team, fn ($q) => $q->whereNull('team_id'))
            ->with('citationQuery')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated alerts for user filtered by team context.
     */
    public function getPaginatedAlerts(User $user, ?Team $team = null, int $perPage = 20)
    {
        $query = CitationAlert::where('user_id', $user->id);

        if ($team) {
            $query->where('team_id', $team->id);
        } else {
            $query->whereNull('team_id');
        }

        return $query
            ->with(['citationQuery', 'citationCheck'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Process completed check and create alerts if needed.
     */
    public function processCheckCompletion(CitationCheck $check): void
    {
        $query = $check->citationQuery;

        // Get the previous check for comparison
        $previousCheck = CitationCheck::where('citation_query_id', $query->id)
            ->where('platform', $check->platform)
            ->where('status', CitationCheck::STATUS_COMPLETED)
            ->where('id', '!=', $check->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Create alerts based on citation status changes
        if ($previousCheck) {
            // Was not cited, now is cited -> new citation
            if (! $previousCheck->is_cited && $check->is_cited) {
                CitationAlert::createNewCitationAlert($check);
            }
            // Was cited, now is not cited -> lost citation
            elseif ($previousCheck->is_cited && ! $check->is_cited) {
                CitationAlert::createLostCitationAlert($check);
            }
        } elseif ($check->is_cited) {
            // First check and is cited -> new citation
            CitationAlert::createNewCitationAlert($check);
        }
    }

    /**
     * Get citation usage summary for a user.
     */
    public function getUsageSummary(User $user, ?Team $team = null): array
    {
        $maxQueries = $this->getMaxQueries($user);
        $queriesCreated = $this->getQueriesCreated($user, $team);
        $dailyLimit = $this->getDailyCheckLimit($user);
        $checksToday = $this->getChecksPerformedToday($user);

        // Get platform costs for token-based users
        $platformCosts = [];
        foreach ($this->getAvailablePlatforms($user) as $platform) {
            $platformCosts[$platform] = $this->getTokenCost($platform);
        }

        return [
            'can_access' => $this->canAccessCitations($user),
            'can_access_ga4' => $this->ga4Service->canAccessGA4($user),
            'queries_created' => $queriesCreated,
            'queries_limit' => $maxQueries,
            'queries_remaining' => $this->getQueriesRemaining($user, $team),
            'queries_is_unlimited' => $maxQueries === -1,
            'can_create_query' => $this->canCreateQuery($user, $team),
            'checks_today' => $checksToday,
            'checks_limit' => $dailyLimit,
            'checks_remaining' => $this->getChecksRemainingToday($user),
            'checks_is_unlimited' => $dailyLimit === -1,
            'can_perform_check' => $this->canPerformCheck($user),
            'available_platforms' => $this->getAvailablePlatforms($user),
            'available_frequencies' => $this->getAvailableFrequencies($user),
            'token_balance' => $user->token_balance ?? 0,
            'platform_costs' => $platformCosts,
        ];
    }

    /**
     * Get queries due for scheduled checks.
     */
    public function getQueriesDueForCheck(): \Illuminate\Database\Eloquent\Collection
    {
        return CitationQuery::where('is_active', true)
            ->where('frequency', '!=', CitationQuery::FREQUENCY_MANUAL)
            ->where(function ($query) {
                $query->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', now());
            })
            ->with(['user', 'team'])
            ->get();
    }

    /**
     * Get unread alerts for a user.
     */
    public function getUnreadAlerts(User $user, ?Team $team = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CitationAlert::where('user_id', $user->id)
            ->where('is_read', false);

        if ($team) {
            $query->where('team_id', $team->id);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Mark alerts as read.
     */
    public function markAlertsAsRead(array $alertIds, User $user): int
    {
        return CitationAlert::whereIn('id', $alertIds)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get citation trends data for a user/team.
     */
    public function getTrends(User $user, ?Team $team = null, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $query = CitationCheck::where('user_id', $user->id)
            ->where('status', CitationCheck::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate);

        if ($team) {
            $query->where('team_id', $team->id);
        }

        $checks = $query->get();

        // Group by date
        $byDate = $checks->groupBy(fn ($check) => $check->created_at->format('Y-m-d'));

        // Group by platform
        $byPlatform = $checks->groupBy('platform');

        // Calculate citation rates
        $trends = [];
        foreach ($byDate as $date => $dayChecks) {
            $cited = $dayChecks->where('is_cited', true)->count();
            $total = $dayChecks->count();

            $trends[] = [
                'date' => $date,
                'total_checks' => $total,
                'cited' => $cited,
                'not_cited' => $total - $cited,
                'citation_rate' => $total > 0 ? round(($cited / $total) * 100, 1) : 0,
            ];
        }

        // Platform breakdown
        $platformStats = [];
        foreach ($byPlatform as $platform => $platformChecks) {
            $cited = $platformChecks->where('is_cited', true)->count();
            $total = $platformChecks->count();

            $platformStats[$platform] = [
                'total_checks' => $total,
                'cited' => $cited,
                'citation_rate' => $total > 0 ? round(($cited / $total) * 100, 1) : 0,
            ];
        }

        return [
            'daily_trends' => $trends,
            'platform_stats' => $platformStats,
            'total_checks' => $checks->count(),
            'total_cited' => $checks->where('is_cited', true)->count(),
            'overall_citation_rate' => $checks->count() > 0
                ? round(($checks->where('is_cited', true)->count() / $checks->count()) * 100, 1)
                : 0,
        ];
    }
}
