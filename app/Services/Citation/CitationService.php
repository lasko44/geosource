<?php

namespace App\Services\Citation;

use App\Models\CitationAlert;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Carbon;

class CitationService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Check if user can access citation features.
     */
    public function canAccessCitations(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $limit = $this->subscriptionService->getLimit($user, 'citation_queries');

        return $limit !== null && $limit !== 0;
    }

    /**
     * Get available platforms for the user.
     */
    public function getAvailablePlatforms(User $user): array
    {
        // All supported platforms for citation checking
        // AI Platforms:
        // - Perplexity: Native web search with source URLs
        // - Claude: Tavily Search + Claude analysis
        // - OpenAI: Tavily Search + GPT-4o analysis
        // - Gemini: Google Search Grounding
        // - DeepSeek: DeepSeek AI
        // Search/Social Platforms:
        // - Google: Google Search results + AI Overviews
        // - YouTube: Video search for brand mentions
        // - Facebook: Social mentions via site search
        $supportedPlatforms = [
            'perplexity', 'claude', 'openai', 'gemini', 'deepseek',
            'google', 'youtube', 'facebook',
        ];

        if ($user->is_admin) {
            return $supportedPlatforms;
        }

        $userPlatforms = $this->subscriptionService->getLimit($user, 'citation_platforms') ?? [];

        return array_intersect($userPlatforms, $supportedPlatforms);
    }

    /**
     * Get available frequencies for the user.
     */
    public function getAvailableFrequencies(User $user): array
    {
        if ($user->is_admin) {
            return [
                CitationQuery::FREQUENCY_MANUAL,
                CitationQuery::FREQUENCY_DAILY,
                CitationQuery::FREQUENCY_WEEKLY,
            ];
        }

        return $this->subscriptionService->getLimit($user, 'citation_frequency') ?? [];
    }

    /**
     * Get the maximum number of queries allowed.
     */
    public function getMaxQueries(User $user): int
    {
        if ($user->is_admin) {
            return -1;
        }

        return $this->subscriptionService->getLimit($user, 'citation_queries') ?? 0;
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
     */
    public function getDailyCheckLimit(User $user): int
    {
        if ($user->is_admin) {
            return -1;
        }

        return $this->subscriptionService->getLimit($user, 'citation_checks_per_day') ?? 0;
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
     * Check if user can perform a check.
     */
    public function canPerformCheck(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $remaining = $this->getChecksRemainingToday($user);

        return $remaining === -1 || $remaining > 0;
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
        ?Team $team = null
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

        return [
            'can_access' => $this->canAccessCitations($user),
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
