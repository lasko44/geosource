<?php

namespace App\Services;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Resolve team context for the dashboard.
     */
    public function resolveTeamContext(User $user, Request $request): array
    {
        $teams = null;
        $currentTeamId = null;
        $currentTeam = null;
        $hasPersonalOption = true;

        if (! $this->subscriptionService->isAgencyTier($user) && ! $user->is_admin) {
            return compact('teams', 'currentTeamId', 'currentTeam', 'hasPersonalOption');
        }

        $userTeams = $user->allTeams();
        $ownedTeams = $user->ownedTeams;
        $ownsAnyTeams = $ownedTeams->count() > 0;
        $hasPersonalOption = $ownsAnyTeams || $user->is_admin;

        $teams = $userTeams->map(fn ($team) => [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_owner' => $team->owner_id === $user->id,
            'members_count' => $team->members()->count(),
            'role' => $team->getUserRole($user),
        ])->values();

        $requestedTeamId = $request->input('team');

        if ($requestedTeamId === 'personal' && $hasPersonalOption) {
            session(['current_team_id' => 'personal']);
        } elseif ($requestedTeamId && $requestedTeamId !== 'personal') {
            $team = $userTeams->firstWhere('id', $requestedTeamId);
            if ($team) {
                $currentTeamId = (int) $requestedTeamId;
                $currentTeam = $team;
                session(['current_team_id' => $currentTeamId]);
            }
        } else {
            $storedTeamId = session('current_team_id');
            if ($storedTeamId && $storedTeamId !== 'personal') {
                $team = $userTeams->firstWhere('id', $storedTeamId);
                if ($team) {
                    $currentTeamId = (int) $storedTeamId;
                    $currentTeam = $team;
                }
            }

            if (! $currentTeamId && ! $hasPersonalOption && $userTeams->count() > 0) {
                $firstTeam = $userTeams->first();
                $currentTeamId = $firstTeam->id;
                $currentTeam = $firstTeam;
                session(['current_team_id' => $currentTeamId]);
            }
        }

        return compact('teams', 'currentTeamId', 'currentTeam', 'hasPersonalOption');
    }

    /**
     * Build scan query based on team context with history limit.
     */
    public function buildDashboardScanQuery(User $user, ?int $currentTeamId, $currentTeam)
    {
        $historyDays = $currentTeam
            ? $currentTeam->owner->getLimit('history_days')
            : $user->getLimit('history_days');

        $query = $currentTeamId
            ? Scan::where('team_id', $currentTeamId)
            : Scan::where('user_id', $user->id);

        if ($historyDays !== -1 && $historyDays !== null) {
            $query->where('created_at', '>=', now()->subDays($historyDays));
        }

        return $query;
    }

    /**
     * Build dashboard stats from scan query.
     */
    public function buildStats($scanQuery): array
    {
        return [
            'total_scans' => (clone $scanQuery)->count(),
            'avg_score' => (float) ((clone $scanQuery)->avg('score') ?? 0),
            'best_score' => (float) ((clone $scanQuery)->max('score') ?? 0),
            'scans_this_week' => (clone $scanQuery)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
        ];
    }

    /**
     * Build citation data for agency tier users.
     */
    public function buildCitationData(User $user, ?int $currentTeamId, bool $canAccessCitations): ?array
    {
        if (! $canAccessCitations) {
            return null;
        }

        $citationQueries = CitationQuery::where(function ($q) use ($user, $currentTeamId) {
            $q->where('user_id', $user->id);
            if ($currentTeamId) {
                $q->orWhere('team_id', $currentTeamId);
            }
        })
            ->with(['checks' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->limit(5)
            ->get();

        $recentChecks = CitationCheck::where(function ($q) use ($user, $currentTeamId) {
            $q->where('user_id', $user->id);
            if ($currentTeamId) {
                $q->orWhere('team_id', $currentTeamId);
            }
        })
            ->where('status', 'completed')
            ->latest()
            ->limit(10)
            ->get();

        $citedCount = $recentChecks->where('is_cited', true)->count();
        $totalChecks = $recentChecks->count();

        return [
            'queries' => $citationQueries->map(fn ($q) => [
                'id' => $q->id,
                'uuid' => $q->uuid,
                'query' => $q->query,
                'domain' => $q->domain,
                'is_cited' => $q->checks->first()?->is_cited,
                'last_checked_at' => $q->last_checked_at?->toISOString(),
            ]),
            'stats' => [
                'total_queries' => CitationQuery::where(function ($q) use ($user, $currentTeamId) {
                    $q->where('user_id', $user->id);
                    if ($currentTeamId) {
                        $q->orWhere('team_id', $currentTeamId);
                    }
                })->count(),
                'cited_count' => $citedCount,
                'total_checks' => $totalChecks,
                'citation_rate' => $totalChecks > 0 ? round(($citedCount / $totalChecks) * 100) : 0,
            ],
        ];
    }

    /**
     * Get usage summary based on team context.
     */
    public function getUsageSummary(User $user, $currentTeam): array
    {
        return $currentTeam
            ? $this->subscriptionService->getTeamUsageSummary($currentTeam)
            : $user->getUsageSummary();
    }

    /**
     * Format current team data for response.
     */
    public function formatCurrentTeam($currentTeam): ?array
    {
        if (! $currentTeam) {
            return null;
        }

        return [
            'id' => $currentTeam->id,
            'name' => $currentTeam->name,
            'slug' => $currentTeam->slug,
        ];
    }
}
