<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    /**
     * Get the user's own plan key (not considering team membership).
     */
    public function getOwnPlanKey(User $user): string
    {
        // Admins always have unlimited access
        if ($user->is_admin) {
            return 'admin';
        }

        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->active()) {
            return 'free';
        }

        $priceId = $subscription->stripe_price;

        // Match price ID to plan
        foreach (config('billing.plans.user') as $key => $plan) {
            if ($plan['price_id'] === $priceId) {
                return $key;
            }
        }

        return 'free';
    }

    /**
     * Get the user's effective plan key (considering team membership).
     * Team members inherit Agency features from their team owner.
     */
    public function getPlanKey(User $user): string
    {
        // First check user's own plan
        $ownPlan = $this->getOwnPlanKey($user);

        // If already admin or agency, return that
        if (in_array($ownPlan, ['admin', 'agency'])) {
            return $ownPlan;
        }

        // Check if user is a member of an Agency team (not owner)
        $agencyTeam = $this->getAgencyTeamMembership($user);
        if ($agencyTeam) {
            return 'agency_member'; // Special key for team members
        }

        return $ownPlan;
    }

    /**
     * Check if user is a member (not owner) of a team owned by an Agency subscriber.
     */
    public function getAgencyTeamMembership(User $user): ?\App\Models\Team
    {
        // Get teams where user is a member (not owner)
        $teams = $user->teams()
            ->where('owner_id', '!=', $user->id)
            ->with('owner')
            ->get();

        foreach ($teams as $team) {
            $ownerPlan = $this->getOwnPlanKey($team->owner);
            if (in_array($ownerPlan, ['agency', 'admin'])) {
                return $team;
            }
        }

        return null;
    }

    /**
     * Check if user has team-based Agency access.
     */
    public function hasTeamAgencyAccess(User $user): bool
    {
        return $this->getAgencyTeamMembership($user) !== null;
    }

    /**
     * Get the user's plan configuration.
     */
    public function getPlan(User $user): array
    {
        $planKey = $this->getPlanKey($user);

        if ($planKey === 'admin') {
            return $this->getAdminPlan();
        }

        if ($planKey === 'free') {
            return config('billing.free');
        }

        // Team members get Agency features
        if ($planKey === 'agency_member') {
            $agencyPlan = config('billing.plans.user.agency');
            $agencyPlan['name'] = 'Agency (Team Member)';

            return $agencyPlan;
        }

        return config("billing.plans.user.{$planKey}");
    }

    /**
     * Get the admin plan (unlimited everything).
     */
    protected function getAdminPlan(): array
    {
        return [
            'name' => 'Admin',
            'features' => ['Unlimited access to all features'],
            'limits' => [
                'scans_per_month' => -1,
                'history_days' => -1,
                'teams_allowed' => -1,
                'team_members' => -1,
                'member_scans_per_month' => -1,
                'recommendations_shown' => -1,
                'white_label' => true,
                'scheduled_scans' => true,
                'pdf_export' => true,
                'bulk_scanning' => true,
                // Citation tracking (unlimited for admins)
                'citation_queries' => -1,
                'citation_checks_per_day' => -1,
                'citation_frequency' => ['manual', 'daily', 'weekly'],
                'citation_platforms' => ['perplexity', 'openai', 'claude', 'gemini'],
                'ga4_connections' => -1,
            ],
        ];
    }

    /**
     * Get a specific limit for the user.
     */
    public function getLimit(User $user, string $limit): mixed
    {
        $plan = $this->getPlan($user);

        return $plan['limits'][$limit] ?? null;
    }

    /**
     * Check if user has a specific feature.
     */
    public function hasFeature(User $user, string $feature): bool
    {
        $limit = $this->getLimit($user, $feature);

        // Boolean features
        if (is_bool($limit)) {
            return $limit;
        }

        // Numeric features (-1 means unlimited, > 0 means has feature)
        if (is_numeric($limit)) {
            return $limit !== 0;
        }

        return false;
    }

    /**
     * Get the number of scans used this month by a user (personal scans only).
     * Includes soft-deleted scans to prevent quota bypass via deletion.
     * Excludes cancelled scans since they don't consume quota.
     * Uses user's timezone for accurate month boundary calculation.
     */
    public function getScansUsedThisMonth(User $user): int
    {
        $startOfMonth = $this->getStartOfMonthForUser($user);

        return $user->scans()
            ->withTrashed() // Include deleted scans to prevent quota bypass
            ->where('created_at', '>=', $startOfMonth)
            ->where('status', '!=', 'cancelled') // Cancelled scans don't count
            ->count();
    }

    /**
     * Get the start of month in UTC, based on user's timezone.
     * This ensures quota resets at midnight in the user's local timezone.
     */
    private function getStartOfMonthForUser(User $user): \Carbon\Carbon
    {
        $timezone = $user->timezone ?? 'UTC';

        return Carbon::now($timezone)->startOfMonth()->utc();
    }

    /**
     * Get the number of scans used this month for a team owner (all their teams' scans + personal scans).
     * Includes soft-deleted scans to prevent quota bypass via deletion.
     * Excludes cancelled scans since they don't consume quota.
     * Includes personal scans to prevent quota bypass by mixing personal and team scans.
     * Uses owner's timezone for accurate month boundary calculation.
     */
    public function getOwnerScansUsedThisMonth(User $owner): int
    {
        // Get all teams owned by this user
        $teamIds = $owner->ownedTeams()->pluck('id');
        $startOfMonth = $this->getStartOfMonthForUser($owner);

        // Count all scans from these teams this month (including deleted)
        // PLUS the owner's personal scans (to prevent mixing personal + team to bypass quota)
        return \App\Models\Scan::withTrashed()
            ->where('created_at', '>=', $startOfMonth)
            ->where('status', '!=', 'cancelled') // Cancelled scans don't count
            ->where(function ($query) use ($owner, $teamIds) {
                $query->whereIn('team_id', $teamIds)
                    ->orWhere(function ($q) use ($owner) {
                        $q->where('user_id', $owner->id)->whereNull('team_id');
                    });
            })
            ->count();
    }

    /**
     * Get the remaining scans for this month.
     */
    public function getScansRemaining(User $user): int
    {
        $limit = $this->getLimit($user, 'scans_per_month');

        // Unlimited
        if ($limit === -1) {
            return -1;
        }

        $used = $this->getScansUsedThisMonth($user);

        return max(0, $limit - $used);
    }

    /**
     * Get the remaining scans for a team owner (considering all team scans).
     */
    public function getOwnerScansRemaining(User $owner): int
    {
        $limit = $this->getLimit($owner, 'scans_per_month');

        // Unlimited
        if ($limit === -1) {
            return -1;
        }

        $used = $this->getOwnerScansUsedThisMonth($owner);

        return max(0, $limit - $used);
    }

    /**
     * Check if user can perform a scan.
     */
    public function canScan(User $user): bool
    {
        // Admins can always scan
        if ($user->is_admin) {
            return true;
        }

        $remaining = $this->getScansRemaining($user);

        return $remaining === -1 || $remaining > 0;
    }

    /**
     * Check if a scan can be performed for a specific team (checks owner's quota).
     */
    public function canScanForTeam(\App\Models\Team $team): bool
    {
        $owner = $team->owner;

        // Admins can always scan
        if ($owner->is_admin) {
            return true;
        }

        $remaining = $this->getOwnerScansRemaining($owner);

        return $remaining === -1 || $remaining > 0;
    }

    /**
     * Get the number of scans a specific member has made for a team this month.
     * Includes soft-deleted scans to prevent quota bypass via deletion.
     */
    public function getMemberScansUsedThisMonth(User $member, \App\Models\Team $team): int
    {
        return \App\Models\Scan::withTrashed()
            ->where('user_id', $member->id)
            ->where('team_id', $team->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    /**
     * Get the per-member scan limit for a team (based on owner's plan).
     */
    public function getMemberScanLimit(\App\Models\Team $team): int
    {
        $owner = $team->owner;

        return $this->getLimit($owner, 'member_scans_per_month') ?? 50;
    }

    /**
     * Check if a team member can scan (within their per-member limit).
     */
    public function canMemberScanForTeam(User $member, \App\Models\Team $team): bool
    {
        // Team owners are not subject to per-member limits
        if ($team->owner_id === $member->id) {
            return true;
        }

        // Admins can always scan
        if ($member->is_admin) {
            return true;
        }

        $limit = $this->getMemberScanLimit($team);
        $used = $this->getMemberScansUsedThisMonth($member, $team);

        return $used < $limit;
    }

    /**
     * Get member's remaining scans for a team this month.
     */
    public function getMemberScansRemaining(User $member, \App\Models\Team $team): int
    {
        // Team owners are not subject to per-member limits
        if ($team->owner_id === $member->id) {
            return -1; // Unlimited (subject to team limit)
        }

        $limit = $this->getMemberScanLimit($team);
        $used = $this->getMemberScansUsedThisMonth($member, $team);

        return max(0, $limit - $used);
    }

    /**
     * Get usage summary for a team context (uses owner's quota).
     */
    public function getTeamUsageSummary(\App\Models\Team $team): array
    {
        $owner = $team->owner;
        $plan = $this->getPlan($owner);
        $planKey = $this->getPlanKey($owner);
        $scansUsed = $this->getOwnerScansUsedThisMonth($owner);
        $scansLimit = $this->getLimit($owner, 'scans_per_month');
        $scansRemaining = $this->getOwnerScansRemaining($owner);

        return [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'],
            'scans_used' => $scansUsed,
            'scans_limit' => $scansLimit,
            'scans_remaining' => $scansRemaining,
            'is_unlimited' => $scansLimit === -1,
            'can_scan' => $this->canScanForTeam($team),
            'features' => $plan['features'] ?? [],
            'limits' => $plan['limits'] ?? [],
            'owner_name' => $owner->name,
        ];
    }

    /**
     * Check if user is on free tier.
     */
    public function isFreeTier(User $user): bool
    {
        return $this->getPlanKey($user) === 'free';
    }

    /**
     * Check if user is on Pro tier.
     */
    public function isProTier(User $user): bool
    {
        return $this->getPlanKey($user) === 'pro';
    }

    /**
     * Check if user is on Agency tier (including team members).
     */
    public function isAgencyTier(User $user): bool
    {
        return in_array($this->getPlanKey($user), ['agency', 'agency_member']);
    }

    /**
     * Check if user owns an Agency subscription (not via team membership).
     */
    public function ownsAgencySubscription(User $user): bool
    {
        return $this->getOwnPlanKey($user) === 'agency';
    }

    /**
     * Check if user has a paid subscription (or team member access).
     */
    public function hasPaidSubscription(User $user): bool
    {
        $planKey = $this->getPlanKey($user);

        return in_array($planKey, ['pro', 'agency', 'agency_member', 'admin']);
    }

    /**
     * Get usage summary for the user.
     */
    public function getUsageSummary(User $user): array
    {
        $plan = $this->getPlan($user);
        $planKey = $this->getPlanKey($user);
        $scansUsed = $this->getScansUsedThisMonth($user);
        $scansLimit = $this->getLimit($user, 'scans_per_month');
        $scansRemaining = $this->getScansRemaining($user);

        return [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'],
            'scans_used' => $scansUsed,
            'scans_limit' => $scansLimit,
            'scans_remaining' => $scansRemaining,
            'is_unlimited' => $scansLimit === -1,
            'can_scan' => $this->canScan($user),
            'features' => $plan['features'] ?? [],
            'limits' => $plan['limits'] ?? [],
        ];
    }

    /**
     * Get recommendations based on plan tier.
     */
    public function filterRecommendations(User $user, array $recommendations): array
    {
        $limit = $this->getLimit($user, 'recommendations_shown');

        // No limit or unlimited
        if ($limit === null || $limit === -1) {
            return $recommendations;
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Check if user should see upgrade prompt.
     */
    public function shouldShowUpgradePrompt(User $user): bool
    {
        // Don't show to admins, agency users, or team members with agency access
        if ($user->is_admin || $this->isAgencyTier($user)) {
            return false;
        }

        // Show if on free tier
        if ($this->isFreeTier($user)) {
            return true;
        }

        // Show to Pro users if they're using > 80% of their scans
        if ($this->isProTier($user)) {
            $scansUsed = $this->getScansUsedThisMonth($user);
            $scansLimit = $this->getLimit($user, 'scans_per_month');

            return $scansUsed > ($scansLimit * 0.8);
        }

        return false;
    }

    /**
     * Check if user can create teams (needs Pro or Agency subscription with available slots).
     */
    public function canCreateTeams(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $teamsAllowed = $this->getOwnPlanLimit($user, 'teams_allowed');

        // No teams allowed on this plan
        if (! $teamsAllowed || $teamsAllowed === 0) {
            return false;
        }

        // Unlimited teams
        if ($teamsAllowed === -1) {
            return true;
        }

        // Check if user has remaining team slots
        return $this->getTeamsRemaining($user) > 0;
    }

    /**
     * Get a limit from user's own plan (not considering team membership).
     */
    public function getOwnPlanLimit(User $user, string $limit): mixed
    {
        $planKey = $this->getOwnPlanKey($user);

        if ($planKey === 'admin') {
            return $this->getAdminPlan()['limits'][$limit] ?? null;
        }

        if ($planKey === 'free') {
            return config("billing.free.limits.{$limit}");
        }

        return config("billing.plans.user.{$planKey}.limits.{$limit}");
    }

    /**
     * Get the number of teams the user is allowed to create.
     */
    public function getTeamsAllowed(User $user): int
    {
        if ($user->is_admin) {
            return -1; // unlimited
        }

        return $this->getOwnPlanLimit($user, 'teams_allowed') ?? 0;
    }

    /**
     * Get the number of teams the user has created.
     */
    public function getTeamsCreated(User $user): int
    {
        return $user->ownedTeams()->count();
    }

    /**
     * Get the number of remaining team slots.
     */
    public function getTeamsRemaining(User $user): int
    {
        $allowed = $this->getTeamsAllowed($user);

        if ($allowed === -1) {
            return -1; // unlimited
        }

        $created = $this->getTeamsCreated($user);

        return max(0, $allowed - $created);
    }

    /**
     * Get the upgrade path for the user.
     */
    public function getUpgradePath(User $user): ?string
    {
        $planKey = $this->getPlanKey($user);

        return match ($planKey) {
            'free' => 'pro',
            'pro' => 'agency',
            default => null,
        };
    }
}
