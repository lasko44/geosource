<?php

namespace Tests\Unit\Services;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Scan;
use App\Models\Team;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    // ==========================================
    // Team Context Resolution Tests
    // ==========================================

    public function test_resolve_team_context_returns_null_for_free_user(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/dashboard');

        $context = $this->dashboardService->resolveTeamContext($user, $request);

        $this->assertNull($context['teams']);
        $this->assertNull($context['currentTeamId']);
        $this->assertNull($context['currentTeam']);
    }

    public function test_resolve_team_context_returns_teams_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $request = Request::create('/dashboard');

        $context = $this->dashboardService->resolveTeamContext($user, $request);

        $this->assertNotNull($context['teams']);
        $this->assertTrue($context['hasPersonalOption']);
    }

    public function test_resolve_team_context_sets_team_from_request(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $request = Request::create('/dashboard', 'GET', ['team' => $team->id]);

        $context = $this->dashboardService->resolveTeamContext($user, $request);

        $this->assertEquals($team->id, $context['currentTeamId']);
        $this->assertEquals($team->id, $context['currentTeam']->id);
    }

    public function test_resolve_team_context_sets_personal_from_request(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $request = Request::create('/dashboard', 'GET', ['team' => 'personal']);

        $context = $this->dashboardService->resolveTeamContext($user, $request);

        $this->assertNull($context['currentTeamId']);
        $this->assertNull($context['currentTeam']);
    }

    public function test_resolve_team_context_restores_from_session(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);

        session(['current_team_id' => $team->id]);
        $request = Request::create('/dashboard');

        $context = $this->dashboardService->resolveTeamContext($user, $request);

        $this->assertEquals($team->id, $context['currentTeamId']);
    }

    // ==========================================
    // Stats Building Tests
    // ==========================================

    public function test_build_stats_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        Scan::factory()->count(3)->create([
            'user_id' => $user->id,
            'score' => 80,
        ]);

        $query = Scan::where('user_id', $user->id);
        $stats = $this->dashboardService->buildStats($query);

        $this->assertArrayHasKey('total_scans', $stats);
        $this->assertArrayHasKey('avg_score', $stats);
        $this->assertArrayHasKey('best_score', $stats);
        $this->assertArrayHasKey('scans_this_week', $stats);
    }

    public function test_build_stats_calculates_correct_values(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'score' => 60]);
        Scan::factory()->create(['user_id' => $user->id, 'score' => 80]);
        Scan::factory()->create(['user_id' => $user->id, 'score' => 100]);

        $query = Scan::where('user_id', $user->id);
        $stats = $this->dashboardService->buildStats($query);

        $this->assertEquals(3, $stats['total_scans']);
        $this->assertEquals(80, $stats['avg_score']);
        $this->assertEquals(100, $stats['best_score']);
    }

    public function test_build_stats_handles_empty_results(): void
    {
        $user = User::factory()->create();
        $query = Scan::where('user_id', $user->id);
        $stats = $this->dashboardService->buildStats($query);

        $this->assertEquals(0, $stats['total_scans']);
        $this->assertEquals(0, $stats['avg_score']);
        $this->assertEquals(0, $stats['best_score']);
    }

    public function test_build_stats_counts_scans_this_week(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        Scan::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(3)]);
        Scan::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(10)]);

        $query = Scan::where('user_id', $user->id);
        $stats = $this->dashboardService->buildStats($query);

        $this->assertEquals(3, $stats['total_scans']);
        $this->assertEquals(2, $stats['scans_this_week']);
    }

    // ==========================================
    // Citation Data Tests
    // ==========================================

    public function test_build_citation_data_returns_null_when_no_access(): void
    {
        $user = User::factory()->create();

        $result = $this->dashboardService->buildCitationData($user, null, false);

        $this->assertNull($result);
    }

    public function test_build_citation_data_returns_queries_for_user(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        CitationQuery::factory()->count(3)->create(['user_id' => $user->id]);

        $result = $this->dashboardService->buildCitationData($user, null, true);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('queries', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertCount(3, $result['queries']);
    }

    public function test_build_citation_data_includes_team_queries(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $user->id]);

        CitationQuery::factory()->create(['user_id' => $user->id, 'team_id' => null]);
        CitationQuery::factory()->create(['user_id' => $user->id, 'team_id' => $team->id]);

        $result = $this->dashboardService->buildCitationData($user, $team->id, true);

        $this->assertEquals(2, $result['stats']['total_queries']);
    }

    public function test_build_citation_data_calculates_citation_rate(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        CitationCheck::factory()->count(3)->create([
            'user_id' => $user->id,
            'citation_query_id' => $query->id,
            'status' => 'completed',
            'is_cited' => true,
        ]);
        CitationCheck::factory()->count(2)->create([
            'user_id' => $user->id,
            'citation_query_id' => $query->id,
            'status' => 'completed',
            'is_cited' => false,
        ]);

        $result = $this->dashboardService->buildCitationData($user, null, true);

        $this->assertEquals(5, $result['stats']['total_checks']);
        $this->assertEquals(3, $result['stats']['cited_count']);
        $this->assertEquals(60, $result['stats']['citation_rate']);
    }

    // ==========================================
    // Dashboard Scan Query Tests
    // ==========================================

    public function test_build_dashboard_scan_query_for_personal(): void
    {
        $user = User::factory()->create();
        Scan::factory()->create(['user_id' => $user->id]);
        Scan::factory()->create(); // Another user's scan

        $query = $this->dashboardService->buildDashboardScanQuery($user, null, null);

        $this->assertEquals(1, $query->count());
    }

    public function test_build_dashboard_scan_query_for_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        Scan::factory()->create(['user_id' => $user->id, 'team_id' => null]);
        Scan::factory()->create(['user_id' => $user->id, 'team_id' => $team->id]);

        $query = $this->dashboardService->buildDashboardScanQuery($user, $team->id, $team);

        $this->assertEquals(1, $query->count());
    }

    // ==========================================
    // Format Current Team Tests
    // ==========================================

    public function test_format_current_team_returns_null_for_no_team(): void
    {
        $result = $this->dashboardService->formatCurrentTeam(null);

        $this->assertNull($result);
    }

    public function test_format_current_team_returns_formatted_data(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Test Team',
            'slug' => 'test-team',
        ]);

        $result = $this->dashboardService->formatCurrentTeam($team);

        $this->assertEquals($team->id, $result['id']);
        $this->assertEquals('Test Team', $result['name']);
        $this->assertEquals('test-team', $result['slug']);
    }

    // ==========================================
    // Usage Summary Tests
    // ==========================================

    public function test_get_usage_summary_for_personal(): void
    {
        $user = User::factory()->create();

        $result = $this->dashboardService->getUsageSummary($user, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scans_used', $result);
        $this->assertArrayHasKey('scans_limit', $result);
    }

    public function test_get_usage_summary_for_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $result = $this->dashboardService->getUsageSummary($user, $team);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scans_used', $result);
    }
}
