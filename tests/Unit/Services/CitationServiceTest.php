<?php

namespace Tests\Unit\Services;

use App\Models\CitationAlert;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Services\Citation\CitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CitationService $citationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->citationService = app(CitationService::class);
    }

    // ==========================================
    // Access Control Tests
    // ==========================================

    public function test_admin_can_access_citations(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertTrue($this->citationService->canAccessCitations($admin));
    }

    public function test_user_with_tokens_can_access_citations(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertTrue($this->citationService->canAccessCitations($user));
    }

    public function test_user_without_tokens_cannot_access_citations(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->citationService->canAccessCitations($user));
    }

    // ==========================================
    // Platform Access Tests
    // ==========================================

    public function test_admin_gets_all_platforms(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $platforms = $this->citationService->getAvailablePlatforms($admin);

        $this->assertContains('perplexity', $platforms);
        $this->assertContains('claude', $platforms);
        $this->assertContains('openai', $platforms);
        $this->assertContains('gemini', $platforms);
        $this->assertContains('google', $platforms);
    }

    public function test_user_with_tokens_gets_all_platforms(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $platforms = $this->citationService->getAvailablePlatforms($user);

        $this->assertNotEmpty($platforms);
        $this->assertContains('perplexity', $platforms);
    }

    public function test_user_without_tokens_gets_no_platforms(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $platforms = $this->citationService->getAvailablePlatforms($user);

        $this->assertEmpty($platforms);
    }

    // ==========================================
    // Token Cost Tests
    // ==========================================

    public function test_get_token_cost_returns_correct_value(): void
    {
        // Check known platforms have costs defined
        $perplexityCost = $this->citationService->getTokenCost('perplexity');

        // Cost should be a non-negative integer
        $this->assertIsInt($perplexityCost);
        $this->assertGreaterThanOrEqual(0, $perplexityCost);
    }

    public function test_get_token_cost_returns_zero_for_unknown_platform(): void
    {
        $cost = $this->citationService->getTokenCost('unknown_platform');

        $this->assertEquals(0, $cost);
    }

    public function test_admin_can_afford_any_check(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertTrue($this->citationService->canAffordCheck($admin, 'perplexity'));
        $this->assertTrue($this->citationService->canAffordCheck($admin, 'claude'));
    }

    public function test_user_with_sufficient_tokens_can_afford_check(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->assertTrue($this->citationService->canAffordCheck($user, 'perplexity'));
    }

    public function test_user_without_sufficient_tokens_cannot_afford_check(): void
    {
        // Get the actual cost for a platform
        $cost = config('tokens.costs.citation_perplexity', 3);
        $user = User::factory()->create(['token_balance' => $cost - 1]);

        $this->assertFalse($this->citationService->canAffordCheck($user, 'perplexity'));
    }

    // ==========================================
    // Frequency Tests
    // ==========================================

    public function test_admin_gets_all_frequencies(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $frequencies = $this->citationService->getAvailableFrequencies($admin);

        $this->assertContains('manual', $frequencies);
        $this->assertContains('daily', $frequencies);
        $this->assertContains('weekly', $frequencies);
    }

    public function test_user_with_tokens_gets_all_frequencies(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $frequencies = $this->citationService->getAvailableFrequencies($user);

        $this->assertContains('manual', $frequencies);
        $this->assertContains('daily', $frequencies);
        $this->assertContains('weekly', $frequencies);
    }

    public function test_user_without_tokens_gets_no_frequencies(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $frequencies = $this->citationService->getAvailableFrequencies($user);

        $this->assertEmpty($frequencies);
    }

    // ==========================================
    // Query Limits Tests
    // ==========================================

    public function test_admin_has_unlimited_queries(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertEquals(-1, $this->citationService->getMaxQueries($admin));
    }

    public function test_user_with_tokens_has_unlimited_queries(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertEquals(-1, $this->citationService->getMaxQueries($user));
    }

    public function test_user_without_tokens_has_no_queries(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals(0, $this->citationService->getMaxQueries($user));
    }

    public function test_get_queries_created_counts_user_queries(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        CitationQuery::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $this->citationService->getQueriesCreated($user));
    }

    public function test_get_queries_created_filters_by_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Create personal queries
        CitationQuery::factory()->count(2)->create([
            'user_id' => $owner->id,
            'team_id' => null,
        ]);

        // Create team queries
        CitationQuery::factory()->count(3)->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        // Personal count (without team)
        $this->assertEquals(2, $this->citationService->getQueriesCreated($owner));

        // Team count
        $this->assertEquals(3, $this->citationService->getQueriesCreated($owner, $team));
    }

    public function test_get_queries_remaining_returns_unlimited_for_token_user(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertEquals(-1, $this->citationService->getQueriesRemaining($user));
    }

    public function test_can_create_query_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertTrue($this->citationService->canCreateQuery($admin));
    }

    public function test_can_create_query_returns_true_for_user_with_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertTrue($this->citationService->canCreateQuery($user));
    }

    public function test_can_create_query_returns_false_for_user_without_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->citationService->canCreateQuery($user));
    }

    // ==========================================
    // Daily Check Limit Tests
    // ==========================================

    public function test_admin_has_unlimited_daily_checks(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertEquals(-1, $this->citationService->getDailyCheckLimit($admin));
    }

    public function test_user_with_tokens_has_unlimited_daily_checks(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertEquals(-1, $this->citationService->getDailyCheckLimit($user));
    }

    public function test_get_checks_performed_today_counts_correctly(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        // Create checks today
        CitationCheck::factory()->count(3)->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Create checks yesterday
        CitationCheck::factory()->count(2)->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $this->assertEquals(3, $this->citationService->getChecksPerformedToday($user));
    }

    public function test_get_checks_remaining_returns_unlimited_for_token_user(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertEquals(-1, $this->citationService->getChecksRemainingToday($user));
    }

    public function test_can_perform_check_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $this->assertTrue($this->citationService->canPerformCheck($admin));
    }

    public function test_can_perform_check_returns_true_for_user_with_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertTrue($this->citationService->canPerformCheck($user));
    }

    public function test_can_perform_check_returns_false_for_user_without_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->citationService->canPerformCheck($user));
    }

    // ==========================================
    // Query Creation Tests
    // ==========================================

    public function test_create_query_creates_manual_query(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $query = $this->citationService->createQuery(
            $user,
            'best CRM software',
            'example.com',
            'Example Corp'
        );

        $this->assertNotNull($query);
        $this->assertEquals($user->id, $query->user_id);
        $this->assertEquals('best CRM software', $query->query);
        $this->assertEquals('example.com', $query->domain);
        $this->assertEquals('Example Corp', $query->brand);
        $this->assertEquals('manual', $query->frequency);
        $this->assertTrue($query->is_active);
        $this->assertNull($query->next_check_at);
    }

    public function test_create_query_with_daily_frequency_sets_next_check(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $query = $this->citationService->createQuery(
            $user,
            'test query',
            'example.com',
            null,
            CitationQuery::FREQUENCY_DAILY
        );

        $this->assertEquals('daily', $query->frequency);
        $this->assertNotNull($query->next_check_at);
        $this->assertTrue($query->next_check_at->isNextDay());
    }

    public function test_create_query_with_weekly_frequency_sets_next_check(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $query = $this->citationService->createQuery(
            $user,
            'test query',
            'example.com',
            null,
            CitationQuery::FREQUENCY_WEEKLY
        );

        $this->assertEquals('weekly', $query->frequency);
        $this->assertNotNull($query->next_check_at);
        // Should be at least 6 days from now (using absolute diff)
        $this->assertGreaterThanOrEqual(6, abs($query->next_check_at->diffInDays(now())));
    }

    public function test_create_query_with_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $query = $this->citationService->createQuery(
            $owner,
            'test query',
            'example.com',
            null,
            CitationQuery::FREQUENCY_MANUAL,
            $team
        );

        $this->assertEquals($team->id, $query->team_id);
    }

    public function test_create_query_with_scheduled_platforms(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $query = $this->citationService->createQuery(
            $user,
            'test query',
            'example.com',
            null,
            CitationQuery::FREQUENCY_DAILY,
            null,
            ['perplexity', 'claude']
        );

        $this->assertEquals(['perplexity', 'claude'], $query->scheduled_platforms);
    }

    // ==========================================
    // Check Creation Tests
    // ==========================================

    public function test_create_check_creates_pending_check(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        $check = $this->citationService->createCheck($query, 'perplexity', $user);

        $this->assertNotNull($check);
        $this->assertEquals($query->id, $check->citation_query_id);
        $this->assertEquals($user->id, $check->user_id);
        $this->assertEquals('perplexity', $check->platform);
        $this->assertEquals('pending', $check->status);
    }

    public function test_create_check_inherits_team_id(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $check = $this->citationService->createCheck($query, 'claude', $owner);

        $this->assertEquals($team->id, $check->team_id);
    }

    // ==========================================
    // Check Completion & Alerts Tests
    // ==========================================

    public function test_process_check_completion_creates_new_citation_alert(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        // First check, and it's cited
        $check = CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
        ]);

        $this->citationService->processCheckCompletion($check);

        $this->assertDatabaseHas('citation_alerts', [
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'type' => 'new_citation',
        ]);
    }

    public function test_process_check_completion_creates_lost_citation_alert(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        // Create previous cited check
        CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
            'created_at' => now()->subDay(),
        ]);

        // New check, not cited
        $check = CitationCheck::factory()->notCited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
        ]);

        $this->citationService->processCheckCompletion($check);

        $this->assertDatabaseHas('citation_alerts', [
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'type' => 'lost_citation',
        ]);
    }

    public function test_process_check_completion_no_alert_if_still_cited(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        // Create previous cited check
        CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
            'created_at' => now()->subDay(),
        ]);

        // New check, still cited
        $check = CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
        ]);

        $initialAlertCount = CitationAlert::count();

        $this->citationService->processCheckCompletion($check);

        // No new alerts should be created
        $this->assertEquals($initialAlertCount, CitationAlert::count());
    }

    // ==========================================
    // Usage Summary Tests
    // ==========================================

    public function test_get_usage_summary_returns_correct_structure(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);

        $summary = $this->citationService->getUsageSummary($user);

        $this->assertArrayHasKey('can_access', $summary);
        $this->assertArrayHasKey('queries_created', $summary);
        $this->assertArrayHasKey('queries_limit', $summary);
        $this->assertArrayHasKey('queries_remaining', $summary);
        $this->assertArrayHasKey('queries_is_unlimited', $summary);
        $this->assertArrayHasKey('checks_today', $summary);
        $this->assertArrayHasKey('checks_limit', $summary);
        $this->assertArrayHasKey('can_perform_check', $summary);
        $this->assertArrayHasKey('available_platforms', $summary);
        $this->assertArrayHasKey('available_frequencies', $summary);
        $this->assertArrayHasKey('token_balance', $summary);
        $this->assertArrayHasKey('platform_costs', $summary);
    }

    public function test_get_usage_summary_for_token_user(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);
        CitationQuery::factory()->count(2)->create(['user_id' => $user->id]);

        $summary = $this->citationService->getUsageSummary($user);

        $this->assertTrue($summary['can_access']);
        $this->assertEquals(2, $summary['queries_created']);
        $this->assertEquals(-1, $summary['queries_limit']);
        $this->assertTrue($summary['queries_is_unlimited']);
        $this->assertTrue($summary['can_perform_check']);
        $this->assertEquals(50, $summary['token_balance']);
    }

    public function test_get_usage_summary_for_user_without_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $summary = $this->citationService->getUsageSummary($user);

        $this->assertFalse($summary['can_access']);
        $this->assertEquals(0, $summary['queries_limit']);
        $this->assertFalse($summary['queries_is_unlimited']);
        $this->assertFalse($summary['can_perform_check']);
        $this->assertEmpty($summary['available_platforms']);
    }

    // ==========================================
    // Scheduled Check Tests
    // ==========================================

    public function test_get_queries_due_for_check_returns_due_queries(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        // Query due now
        CitationQuery::factory()->daily()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'next_check_at' => now()->subHour(),
        ]);

        // Query not due yet
        CitationQuery::factory()->daily()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'next_check_at' => now()->addDay(),
        ]);

        // Manual query (never due)
        CitationQuery::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'frequency' => 'manual',
        ]);

        // Inactive query
        CitationQuery::factory()->daily()->inactive()->create([
            'user_id' => $user->id,
            'next_check_at' => now()->subHour(),
        ]);

        $dueQueries = $this->citationService->getQueriesDueForCheck();

        $this->assertCount(1, $dueQueries);
    }

    // ==========================================
    // Alert Tests
    // ==========================================

    public function test_get_unread_alerts_returns_only_unread(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);
        $check = CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
        ]);

        // Create unread alert
        CitationAlert::createNewCitationAlert($check);

        // Create read alert
        CitationAlert::create([
            'citation_query_id' => $query->id,
            'citation_check_id' => $check->id,
            'user_id' => $user->id,
            'type' => 'new_citation',
            'platform' => 'perplexity',
            'message' => 'Test alert message',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $unread = $this->citationService->getUnreadAlerts($user);

        $this->assertCount(1, $unread);
    }

    public function test_mark_alerts_as_read(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);
        $check = CitationCheck::factory()->cited()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'claude',
        ]);

        $alert = CitationAlert::create([
            'citation_query_id' => $query->id,
            'citation_check_id' => $check->id,
            'user_id' => $user->id,
            'type' => 'new_citation',
            'platform' => 'claude',
            'message' => 'Test alert message',
            'is_read' => false,
        ]);

        $count = $this->citationService->markAlertsAsRead([$alert->id], $user);

        $this->assertEquals(1, $count);

        $alert->refresh();
        $this->assertTrue($alert->is_read);
        $this->assertNotNull($alert->read_at);
    }

    // ==========================================
    // Trends Tests
    // ==========================================

    public function test_get_trends_returns_correct_structure(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        // Create some checks
        CitationCheck::factory()->cited()->count(3)->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
        ]);

        CitationCheck::factory()->notCited()->count(2)->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'claude',
        ]);

        $trends = $this->citationService->getTrends($user);

        $this->assertArrayHasKey('daily_trends', $trends);
        $this->assertArrayHasKey('platform_stats', $trends);
        $this->assertArrayHasKey('total_checks', $trends);
        $this->assertArrayHasKey('total_cited', $trends);
        $this->assertArrayHasKey('overall_citation_rate', $trends);

        $this->assertEquals(5, $trends['total_checks']);
        $this->assertEquals(3, $trends['total_cited']);
    }
}
