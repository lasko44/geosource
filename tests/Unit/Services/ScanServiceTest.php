<?php

namespace Tests\Unit\Services;

use App\Models\Scan;
use App\Models\Team;
use App\Models\User;
use App\Services\ScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScanService $scanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanService = app(ScanService::class);
    }

    // ==========================================
    // Cooldown Tests
    // ==========================================

    public function test_get_cooldown_minutes_returns_5_for_basic(): void
    {
        $this->assertEquals(5, $this->scanService->getCooldownMinutes('basic'));
    }

    public function test_get_cooldown_minutes_returns_1_for_pro(): void
    {
        $this->assertEquals(1, $this->scanService->getCooldownMinutes('pro'));
    }

    public function test_get_cooldown_minutes_returns_1_for_full(): void
    {
        $this->assertEquals(1, $this->scanService->getCooldownMinutes('full'));
    }

    public function test_check_cooldown_returns_null_for_no_recent_scan(): void
    {
        $user = User::factory()->create();

        $result = $this->scanService->checkCooldown('https://example.com', $user->id, 'basic');

        $this->assertNull($result);
    }

    public function test_check_cooldown_returns_data_for_recent_scan(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'created_at' => now()->subMinutes(2),
        ]);

        $result = $this->scanService->checkCooldown('https://example.com', $user->id, 'basic');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('scan', $result);
        $this->assertArrayHasKey('minutes_remaining', $result);
        $this->assertArrayHasKey('available_at', $result);
    }

    public function test_check_cooldown_ignores_failed_scans(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'failed',
            'created_at' => now()->subMinute(),
        ]);

        $result = $this->scanService->checkCooldown('https://example.com', $user->id, 'basic');

        $this->assertNull($result);
    }

    public function test_check_cooldown_respects_tier_cooldown_time(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'created_at' => now()->subMinutes(3),
        ]);

        // Pro has 1 minute cooldown, so 3 minutes ago should be clear
        $proResult = $this->scanService->checkCooldown('https://example.com', $user->id, 'pro');
        $this->assertNull($proResult);

        // Basic has 5 minute cooldown, so 3 minutes ago should still be on cooldown
        $basicResult = $this->scanService->checkCooldown('https://example.com', $user->id, 'basic');
        $this->assertNotNull($basicResult);
    }

    // ==========================================
    // Token Cost Tests
    // ==========================================

    public function test_get_token_cost_returns_zero_for_basic(): void
    {
        $this->assertEquals(0, $this->scanService->getTokenCost('basic'));
    }

    public function test_get_token_cost_returns_configured_cost_for_pro(): void
    {
        $expectedCost = config('tokens.costs.scan_pro', 0);
        $this->assertEquals($expectedCost, $this->scanService->getTokenCost('pro'));
    }

    public function test_get_token_cost_returns_configured_cost_for_full(): void
    {
        $expectedCost = config('tokens.costs.scan_full', 0);
        $this->assertEquals($expectedCost, $this->scanService->getTokenCost('full'));
    }

    // ==========================================
    // Subscription Covers Tier Tests
    // ==========================================

    public function test_subscription_covers_tier_returns_true_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->scanService->subscriptionCoversTier($user, 'full'));
    }

    public function test_subscription_covers_tier_returns_true_for_basic(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->scanService->subscriptionCoversTier($user, 'basic'));
    }

    // ==========================================
    // Filter Cooldown URLs Tests
    // ==========================================

    public function test_filter_cooldown_urls_separates_urls(): void
    {
        $user = User::factory()->create();

        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://oncooldown.com',
            'status' => 'completed',
            'created_at' => now()->subMinute(),
        ]);

        $urls = ['https://oncooldown.com', 'https://available.com'];
        $result = $this->scanService->filterCooldownUrls($urls, $user->id, 'basic');

        $this->assertContains('https://available.com', $result['to_scan']);
        $this->assertContains('https://oncooldown.com', $result['on_cooldown']);
    }

    public function test_filter_cooldown_urls_all_available(): void
    {
        $user = User::factory()->create();

        $urls = ['https://site1.com', 'https://site2.com'];
        $result = $this->scanService->filterCooldownUrls($urls, $user->id, 'basic');

        $this->assertCount(2, $result['to_scan']);
        $this->assertEmpty($result['on_cooldown']);
    }

    // ==========================================
    // Check Bulk Quota Tests
    // ==========================================

    public function test_check_bulk_quota_returns_null_when_sufficient(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $result = $this->scanService->checkBulkQuota($user, null, 5);

        $this->assertNull($result);
    }

    // ==========================================
    // Current Team Tests
    // ==========================================

    public function test_get_current_team_returns_null_for_personal(): void
    {
        $user = User::factory()->create();
        session(['current_team_id' => 'personal']);

        $result = $this->scanService->getCurrentTeam($user);

        $this->assertNull($result);
    }

    public function test_get_current_team_returns_null_when_no_session(): void
    {
        $user = User::factory()->create();

        $result = $this->scanService->getCurrentTeam($user);

        $this->assertNull($result);
    }

    public function test_get_current_team_returns_team_from_session(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        session(['current_team_id' => $team->id]);

        $result = $this->scanService->getCurrentTeam($user);

        $this->assertNotNull($result);
        $this->assertEquals($team->id, $result->id);
    }

    public function test_get_current_team_id_returns_null_for_personal(): void
    {
        session(['current_team_id' => 'personal']);

        $this->assertNull($this->scanService->getCurrentTeamId());
    }

    public function test_get_current_team_id_returns_integer(): void
    {
        session(['current_team_id' => 123]);

        $this->assertEquals(123, $this->scanService->getCurrentTeamId());
    }

    // ==========================================
    // Validate Team Context Tests
    // ==========================================

    public function test_validate_team_context_returns_null_on_mismatch(): void
    {
        session(['current_team_id' => 'personal']);

        $result = $this->scanService->validateTeamContext(123);

        $this->assertNull($result);
    }

    public function test_validate_team_context_returns_null_for_personal_match(): void
    {
        session(['current_team_id' => 'personal']);

        $result = $this->scanService->validateTeamContext(null);

        $this->assertNull($result);
    }

    // ==========================================
    // Pillar and Tier Tests
    // ==========================================

    public function test_get_user_tier_for_pillars_returns_admin_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertEquals('admin', $this->scanService->getUserTierForPillars($user));
    }

    public function test_get_user_tier_for_pillars_returns_free_for_free_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('free', $this->scanService->getUserTierForPillars($user));
    }

    public function test_pillar_name_to_key_converts_correctly(): void
    {
        $this->assertEquals('local_seo', $this->scanService->pillarNameToKey('Local SEO'));
        $this->assertEquals('google_business', $this->scanService->pillarNameToKey('Google-Business'));
        $this->assertEquals('technical_seo', $this->scanService->pillarNameToKey('Technical SEO'));
    }

    public function test_filter_pillars_for_scan_tier_filters_by_tier(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'requested_tier' => 'basic',
        ]);

        $pillars = [
            ['name' => 'Free Pillar', 'tier' => 'free'],
            ['name' => 'Pro Pillar', 'tier' => 'pro'],
            ['name' => 'Agency Pillar', 'tier' => 'agency'],
        ];

        $filtered = $this->scanService->filterPillarsForScanTier($pillars, $scan, $user);

        $this->assertCount(1, $filtered);
        $this->assertEquals('Free Pillar', $filtered[0]['name']);
    }

    public function test_filter_pillars_for_scan_tier_shows_all_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'requested_tier' => 'basic',
        ]);

        $pillars = [
            ['name' => 'Free Pillar', 'tier' => 'free'],
            ['name' => 'Pro Pillar', 'tier' => 'pro'],
            ['name' => 'Agency Pillar', 'tier' => 'agency'],
        ];

        $filtered = $this->scanService->filterPillarsForScanTier($pillars, $scan, $user);

        $this->assertCount(3, $filtered);
    }

    // ==========================================
    // Scan Filtering and Sorting Tests
    // ==========================================

    public function test_apply_scan_filters_filters_by_search(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        Scan::factory()->create(['user_id' => $user->id, 'url' => 'https://other.com']);

        $query = Scan::where('user_id', $user->id);
        $filtered = $this->scanService->applyScanFilters($query, ['search' => 'example']);

        $this->assertEquals(1, $filtered->count());
    }

    public function test_apply_scan_filters_filters_by_status(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Scan::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $query = Scan::where('user_id', $user->id);
        $filtered = $this->scanService->applyScanFilters($query, ['status' => 'completed']);

        $this->assertEquals(1, $filtered->count());
    }

    public function test_apply_scan_sorting_sorts_by_field(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'score' => 50]);
        Scan::factory()->create(['user_id' => $user->id, 'score' => 100]);

        $query = Scan::where('user_id', $user->id);
        $sorted = $this->scanService->applyScanSorting($query, 'score', 'desc');

        $this->assertEquals(100, $sorted->first()->score);
    }

    public function test_apply_scan_sorting_uses_default_for_invalid_field(): void
    {
        $user = User::factory()->create();

        $query = Scan::where('user_id', $user->id);
        $sorted = $this->scanService->applyScanSorting($query, 'invalid_field', 'desc');

        // Should not throw, uses created_at as default
        $this->assertNotNull($sorted);
    }

    public function test_get_validated_per_page_returns_valid_value(): void
    {
        $this->assertEquals(10, $this->scanService->getValidatedPerPage(10));
        $this->assertEquals(25, $this->scanService->getValidatedPerPage(25));
        $this->assertEquals(50, $this->scanService->getValidatedPerPage(50));
    }

    public function test_get_validated_per_page_returns_default_for_invalid(): void
    {
        $this->assertEquals(10, $this->scanService->getValidatedPerPage(100));
        $this->assertEquals(10, $this->scanService->getValidatedPerPage(7));
    }

    // ==========================================
    // Available Grades Tests
    // ==========================================

    public function test_get_available_grades_returns_distinct_grades(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'grade' => 'A']);
        Scan::factory()->create(['user_id' => $user->id, 'grade' => 'B']);
        Scan::factory()->create(['user_id' => $user->id, 'grade' => 'A']);

        $grades = $this->scanService->getAvailableGrades($user, null);

        $this->assertCount(2, $grades);
        $this->assertContains('A', $grades->toArray());
        $this->assertContains('B', $grades->toArray());
    }

    // ==========================================
    // Cancel Scan Tests
    // ==========================================

    public function test_cancel_scan_cancels_pending_scan(): void
    {
        $scan = Scan::factory()->create(['status' => 'pending']);

        $result = $this->scanService->cancelScan($scan);

        $this->assertTrue($result);
        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_cancel_scan_cancels_processing_scan(): void
    {
        $scan = Scan::factory()->create(['status' => 'processing']);

        $result = $this->scanService->cancelScan($scan);

        $this->assertTrue($result);
        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_cancel_scan_returns_false_for_completed(): void
    {
        $scan = Scan::factory()->create(['status' => 'completed']);

        $result = $this->scanService->cancelScan($scan);

        $this->assertFalse($result);
        $scan->refresh();
        $this->assertEquals('completed', $scan->status);
    }

    // ==========================================
    // Scan Status Data Tests
    // ==========================================

    public function test_get_scan_status_data_returns_correct_structure(): void
    {
        $scan = Scan::factory()->create([
            'status' => 'completed',
            'progress_step' => 'Finished',
            'progress_percent' => 100,
            'score' => 85,
            'grade' => 'B',
        ]);

        $data = $this->scanService->getScanStatusData($scan);

        $this->assertEquals('completed', $data['status']);
        $this->assertEquals('Finished', $data['progress_step']);
        $this->assertEquals(100, $data['progress_percent']);
        $this->assertEquals(85, $data['score']);
        $this->assertEquals('B', $data['grade']);
    }

    // ==========================================
    // Bulk Scan Status Tests
    // ==========================================

    public function test_get_bulk_scan_status_returns_scan_data(): void
    {
        $user = User::factory()->create();
        $scan1 = Scan::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        $scan2 = Scan::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $data = $this->scanService->getBulkScanStatus([$scan1->uuid, $scan2->uuid], $user->id);

        $this->assertCount(2, $data);
        $this->assertEquals($scan1->uuid, $data[0]['uuid']);
        $this->assertEquals('completed', $data[0]['status']);
    }

    public function test_get_bulk_scan_status_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scan1 = Scan::factory()->create(['user_id' => $user->id]);
        $scan2 = Scan::factory()->create(['user_id' => $otherUser->id]);

        $data = $this->scanService->getBulkScanStatus([$scan1->uuid, $scan2->uuid], $user->id);

        $this->assertCount(1, $data);
    }

    // ==========================================
    // Format Cooldown Response Tests
    // ==========================================

    public function test_format_cooldown_response_when_on_cooldown(): void
    {
        $scan = Scan::factory()->create();
        $cooldown = [
            'scan' => $scan,
            'minutes_remaining' => 3,
            'available_at' => now()->addMinutes(3),
        ];

        $response = $this->scanService->formatCooldownResponse($cooldown, 'basic');

        $this->assertTrue($response['on_cooldown']);
        $this->assertEquals(3, $response['minutes_remaining']);
        $this->assertEquals($scan->uuid, $response['existing_scan_uuid']);
    }

    public function test_format_cooldown_response_when_not_on_cooldown(): void
    {
        $response = $this->scanService->formatCooldownResponse(null, 'basic');

        $this->assertFalse($response['on_cooldown']);
    }

    // ==========================================
    // Build Scan List Query Tests
    // ==========================================

    public function test_build_scan_list_query_for_personal(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id]);
        Scan::factory()->create(); // Another user

        $query = $this->scanService->buildScanListQuery($user, null);

        $this->assertEquals(1, $query->count());
    }

    public function test_build_scan_list_query_for_team_as_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);

        Scan::factory()->create(['user_id' => $user->id, 'team_id' => $team->id]);
        Scan::factory()->create(['user_id' => $user->id, 'team_id' => null]);

        $query = $this->scanService->buildScanListQuery($user, $team->id);

        $this->assertEquals(1, $query->count());
    }
}
