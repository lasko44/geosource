<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    /**
     * Get the user's current plan key.
     */
    public function getPlanKey(User $user): string
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
                'team_members' => -1,
                'competitor_tracking' => -1,
                'recommendations_shown' => -1,
                'api_access' => true,
                'white_label' => true,
                'scheduled_scans' => true,
                'pdf_export' => true,
                'csv_export' => true,
                'bulk_scanning' => true,
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
     * Get the number of scans used this month.
     */
    public function getScansUsedThisMonth(User $user): int
    {
        return $user->scans()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
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
     * Check if user is on Agency tier.
     */
    public function isAgencyTier(User $user): bool
    {
        return $this->getPlanKey($user) === 'agency';
    }

    /**
     * Check if user has a paid subscription.
     */
    public function hasPaidSubscription(User $user): bool
    {
        $planKey = $this->getPlanKey($user);

        return in_array($planKey, ['pro', 'agency', 'admin']);
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
        // Don't show to admins or agency users
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
