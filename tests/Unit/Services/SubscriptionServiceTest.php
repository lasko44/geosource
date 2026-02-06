<?php

namespace Tests\Unit\Services;

use App\Models\Scan;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionService = new SubscriptionService;
    }

    // ==========================================
    // Plan Detection Tests
    // ==========================================

    public function test_get_own_plan_key_returns_free_for_user_without_subscription(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals('free', $this->subscriptionService->getOwnPlanKey($user));
    }

    public function test_get_own_plan_key_returns_admin_for_admin_user(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertEquals('admin', $this->subscriptionService->getOwnPlanKey($user));
    }

    public function test_get_plan_key_returns_free_for_regular_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals('free', $this->subscriptionService->getPlanKey($user));
    }

    public function test_is_free_tier_returns_true_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertTrue($this->subscriptionService->isFreeTier($user));
    }

    public function test_is_free_tier_returns_false_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertFalse($this->subscriptionService->isFreeTier($user));
    }

    // ==========================================
    // Feature Access Tests
    // ==========================================

    public function test_has_feature_returns_false_for_free_user_pdf_export(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->subscriptionService->hasFeature($user, 'pdf_export'));
    }

    public function test_has_feature_returns_true_for_admin_any_feature(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->subscriptionService->hasFeature($user, 'pdf_export'));
        $this->assertTrue($this->subscriptionService->hasFeature($user, 'white_label'));
        $this->assertTrue($this->subscriptionService->hasFeature($user, 'scheduled_scans'));
    }

    public function test_get_limit_returns_correct_value_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals(7, $this->subscriptionService->getLimit($user, 'history_days'));
        $this->assertEquals(0, $this->subscriptionService->getLimit($user, 'teams_allowed'));
        $this->assertEquals(3, $this->subscriptionService->getLimit($user, 'recommendations_shown'));
    }

    public function test_get_limit_returns_unlimited_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertEquals(-1, $this->subscriptionService->getLimit($user, 'scans_per_month'));
        $this->assertEquals(-1, $this->subscriptionService->getLimit($user, 'history_days'));
        $this->assertEquals(-1, $this->subscriptionService->getLimit($user, 'teams_allowed'));
    }

    // ==========================================
    // Scan Quota Tests
    // ==========================================

    public function test_get_scans_used_this_month_counts_correctly(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        // Create scans this month
        Scan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Create a scan from last month (should not count)
        Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);

        $this->assertEquals(3, $this->subscriptionService->getScansUsedThisMonth($user));
    }

    public function test_get_scans_used_this_month_includes_soft_deleted_scans(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        // Create and delete a scan
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        $scan->delete();

        // Create another active scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Both should count (deleted + active = 2)
        $this->assertEquals(2, $this->subscriptionService->getScansUsedThisMonth($user));
    }

    public function test_get_scans_used_this_month_excludes_cancelled_scans(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        // Create completed scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Create cancelled scan (should not count)
        Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->assertEquals(1, $this->subscriptionService->getScansUsedThisMonth($user));
    }

    public function test_can_scan_returns_true_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        // Free users have unlimited basic scans
        $this->assertTrue($this->subscriptionService->canScan($user));
    }

    public function test_can_scan_returns_true_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->subscriptionService->canScan($user));
    }

    public function test_get_scans_remaining_returns_unlimited_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        // Free users have unlimited basic scans
        $this->assertEquals(-1, $this->subscriptionService->getScansRemaining($user));
    }

    // ==========================================
    // Team Feature Tests
    // ==========================================

    public function test_can_create_teams_returns_false_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->subscriptionService->canCreateTeams($user));
    }

    public function test_can_create_teams_returns_true_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->subscriptionService->canCreateTeams($user));
    }

    public function test_get_teams_allowed_returns_zero_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals(0, $this->subscriptionService->getTeamsAllowed($user));
    }

    public function test_get_teams_allowed_returns_unlimited_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertEquals(-1, $this->subscriptionService->getTeamsAllowed($user));
    }

    public function test_get_teams_created_counts_owned_teams(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        // Create teams owned by this user
        Team::factory()->count(3)->create(['owner_id' => $user->id]);

        // Create a team owned by someone else
        Team::factory()->create();

        $this->assertEquals(3, $this->subscriptionService->getTeamsCreated($user));
    }

    public function test_get_teams_remaining_calculates_correctly(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        // Admin has unlimited teams
        $this->assertEquals(-1, $this->subscriptionService->getTeamsRemaining($user));
    }

    // ==========================================
    // Team Scan Tests
    // ==========================================

    public function test_can_scan_for_team_returns_true_for_admin_owner(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->subscriptionService->canScanForTeam($team));
    }

    public function test_get_owner_scans_used_this_month_counts_team_scans(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Create scans for the team
        Scan::factory()->count(2)->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        // Create personal scans for the owner (should also count)
        Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => null,
            'status' => 'completed',
        ]);

        // Should count both team and personal scans
        $this->assertEquals(3, $this->subscriptionService->getOwnerScansUsedThisMonth($owner));
    }

    public function test_get_member_scans_used_this_month_counts_member_team_scans(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        // Create scans by member for this team
        Scan::factory()->count(5)->create([
            'user_id' => $member->id,
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        $this->assertEquals(5, $this->subscriptionService->getMemberScansUsedThisMonth($member, $team));
    }

    public function test_can_member_scan_for_team_returns_true_for_owner(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Owner is not subject to per-member limits
        $this->assertTrue($this->subscriptionService->canMemberScanForTeam($owner, $team));
    }

    // ==========================================
    // Token Integration Tests
    // ==========================================

    public function test_has_tokens_for_delegates_to_token_service(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        // Should return true for features the user has tokens for
        $this->assertTrue($this->subscriptionService->hasTokensFor($user, 'scan_pro')); // costs 5
        $this->assertTrue($this->subscriptionService->hasTokensFor($user, 'scan_full')); // costs 10, user has exactly 10

        // Should return false when user doesn't have enough tokens
        $user2 = User::factory()->create(['token_balance' => 9]);
        $this->assertFalse($this->subscriptionService->hasTokensFor($user2, 'scan_full')); // costs 10, user only has 9
    }

    public function test_get_token_balance_returns_user_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 250]);

        $this->assertEquals(250, $this->subscriptionService->getTokenBalance($user));
    }

    public function test_get_token_cost_returns_feature_cost(): void
    {
        $this->assertEquals(5, $this->subscriptionService->getTokenCost('scan_pro'));
        $this->assertEquals(10, $this->subscriptionService->getTokenCost('scan_full'));
        $this->assertEquals(2, $this->subscriptionService->getTokenCost('pdf_export'));
    }

    public function test_can_scan_with_tokens_returns_true_for_basic_tier(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        // Basic scans are always free
        $this->assertTrue($this->subscriptionService->canScanWithTokens($user, 'basic'));
        $this->assertTrue($this->subscriptionService->canScanWithTokens($user, 'FREE'));
    }

    public function test_can_scan_with_tokens_returns_false_for_pro_without_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->subscriptionService->canScanWithTokens($user, 'pro'));
    }

    public function test_can_scan_with_tokens_returns_true_for_pro_with_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertTrue($this->subscriptionService->canScanWithTokens($user, 'pro'));
    }

    public function test_can_scan_with_tokens_returns_true_for_admin_any_tier(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertTrue($this->subscriptionService->canScanWithTokens($user, 'pro'));
        $this->assertTrue($this->subscriptionService->canScanWithTokens($user, 'full'));
    }

    // ==========================================
    // Usage Summary Tests
    // ==========================================

    public function test_get_usage_summary_returns_correct_structure(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);

        $summary = $this->subscriptionService->getUsageSummary($user);

        $this->assertArrayHasKey('plan_key', $summary);
        $this->assertArrayHasKey('plan_name', $summary);
        $this->assertArrayHasKey('scans_used', $summary);
        $this->assertArrayHasKey('scans_limit', $summary);
        $this->assertArrayHasKey('scans_remaining', $summary);
        $this->assertArrayHasKey('is_unlimited', $summary);
        $this->assertArrayHasKey('can_scan', $summary);
    }

    public function test_get_usage_summary_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $summary = $this->subscriptionService->getUsageSummary($user);

        $this->assertEquals('free', $summary['plan_key']);
        $this->assertEquals('Free', $summary['plan_name']);
        $this->assertTrue($summary['can_scan']);
        $this->assertTrue($summary['is_unlimited']); // Free tier has unlimited basic scans
    }

    public function test_get_team_usage_summary_returns_correct_structure(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $summary = $this->subscriptionService->getTeamUsageSummary($team);

        $this->assertArrayHasKey('plan_key', $summary);
        $this->assertArrayHasKey('owner_name', $summary);
        $this->assertArrayHasKey('can_scan', $summary);
        $this->assertEquals($owner->name, $summary['owner_name']);
    }

    // ==========================================
    // Upgrade Path Tests
    // ==========================================

    public function test_get_upgrade_path_returns_pro_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals('pro', $this->subscriptionService->getUpgradePath($user));
    }

    public function test_get_upgrade_path_returns_null_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertNull($this->subscriptionService->getUpgradePath($user));
    }

    public function test_should_show_upgrade_prompt_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertTrue($this->subscriptionService->shouldShowUpgradePrompt($user));
    }

    public function test_should_not_show_upgrade_prompt_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertFalse($this->subscriptionService->shouldShowUpgradePrompt($user));
    }

    // ==========================================
    // Recommendation Filter Tests
    // ==========================================

    public function test_filter_recommendations_limits_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $recommendations = [
            ['title' => 'Rec 1'],
            ['title' => 'Rec 2'],
            ['title' => 'Rec 3'],
            ['title' => 'Rec 4'],
            ['title' => 'Rec 5'],
        ];

        $filtered = $this->subscriptionService->filterRecommendations($user, $recommendations);

        // Free users only see 3 recommendations
        $this->assertCount(3, $filtered);
    }

    public function test_filter_recommendations_returns_all_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $recommendations = [
            ['title' => 'Rec 1'],
            ['title' => 'Rec 2'],
            ['title' => 'Rec 3'],
            ['title' => 'Rec 4'],
            ['title' => 'Rec 5'],
        ];

        $filtered = $this->subscriptionService->filterRecommendations($user, $recommendations);

        // Admin sees all recommendations
        $this->assertCount(5, $filtered);
    }

    // ==========================================
    // Scan Tier Tests
    // ==========================================

    public function test_get_scan_tier_for_user_returns_free_for_free_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals('FREE', $this->subscriptionService->getScanTierForUser($user));
    }

    public function test_get_scan_tier_for_user_returns_agency_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertEquals('AGENCY', $this->subscriptionService->getScanTierForUser($user));
    }
}
