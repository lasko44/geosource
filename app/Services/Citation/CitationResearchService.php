<?php

namespace App\Services\Citation;

use App\Jobs\RunCitationResearchJob;
use App\Models\CitationResearch;
use App\Models\Team;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages citation research operations across AI platforms.
 */
class CitationResearchService
{
    public function __construct(
        protected TokenService $tokenService,
        protected CitationService $citationService,
    ) {}

    /**
     * Get the token cost for a research tier.
     */
    public function getTokenCost(string $tier): int
    {
        $feature = match ($tier) {
            CitationResearch::TIER_RESEARCH_AUDIT, 'research_audit' => 'citation_research_audit',
            default => 'citation_research',
        };

        return config("tokens.costs.{$feature}", 0);
    }

    /**
     * Check if user can afford a research at the given tier.
     */
    public function canAfford(User $user, string $tier): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $cost = $this->getTokenCost($tier);

        return ($user->token_balance ?? 0) >= $cost;
    }

    /**
     * Check if user can access the research feature.
     */
    public function canAccessResearch(User $user): bool
    {
        return $this->citationService->canAccessCitations($user);
    }

    /**
     * Create a new citation research with token deduction.
     */
    public function createResearch(
        User $user,
        string $topic,
        string $tier,
        ?Team $team = null
    ): CitationResearch {
        $tokenCost = $this->getTokenCost($tier);

        return DB::transaction(function () use ($user, $topic, $tier, $team, $tokenCost) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // Verify token balance
            if (! $lockedUser->is_admin && $tokenCost > 0 && ($lockedUser->token_balance ?? 0) < $tokenCost) {
                throw new \RuntimeException("Insufficient tokens. You need {$tokenCost} tokens.");
            }

            // Deduct tokens
            if (! $lockedUser->is_admin && $tokenCost > 0) {
                $feature = $tier === CitationResearch::TIER_RESEARCH_AUDIT
                    ? 'citation_research_audit'
                    : 'citation_research';

                $this->tokenService->spend($lockedUser, $feature, [
                    'topic' => $topic,
                    'tier' => $tier,
                ]);
            }

            // Create research record
            return CitationResearch::create([
                'user_id' => $lockedUser->id,
                'team_id' => $team?->id,
                'topic' => $topic,
                'tier' => $tier,
                'status' => CitationResearch::STATUS_PENDING,
                'tokens_charged' => $tokenCost > 0,
                'tokens_amount' => $tokenCost,
            ]);
        });
    }

    /**
     * Dispatch the research job.
     */
    public function dispatchResearch(CitationResearch $research): void
    {
        RunCitationResearchJob::dispatch($research);
    }

    /**
     * Get research records for a user, filtered by team context.
     */
    public function getResearchForUser(User $user, ?Team $team = null, int $limit = 20): Collection
    {
        $query = CitationResearch::where('user_id', $user->id);

        if ($team) {
            $query->where('team_id', $team->id);
        } else {
            $query->whereNull('team_id');
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get research usage summary for a user.
     */
    public function getUsageSummary(User $user): array
    {
        $thisMonth = CitationResearch::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $completed = CitationResearch::where('user_id', $user->id)
            ->where('status', CitationResearch::STATUS_COMPLETED)
            ->count();

        $platformCosts = [
            'research' => $this->getTokenCost(CitationResearch::TIER_RESEARCH),
            'research_audit' => $this->getTokenCost(CitationResearch::TIER_RESEARCH_AUDIT),
        ];

        return [
            'can_access' => $this->canAccessResearch($user),
            'can_afford_research' => $this->canAfford($user, CitationResearch::TIER_RESEARCH),
            'can_afford_audit' => $this->canAfford($user, CitationResearch::TIER_RESEARCH_AUDIT),
            'research_this_month' => $thisMonth,
            'total_completed' => $completed,
            'token_balance' => $user->token_balance ?? 0,
            'costs' => $platformCosts,
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
}
