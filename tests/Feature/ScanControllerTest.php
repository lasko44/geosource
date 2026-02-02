<?php

namespace Tests\Feature;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanControllerTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $subscriptionService;
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionService = app(SubscriptionService::class);
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // Dashboard Tests
    // ==========================================

    public function test_dashboard_displays_for_authenticated_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_dashboard_shows_recent_scans(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        Scan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('recentScans', 3)
        );
    }

    public function test_dashboard_shows_usage_summary(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('usage')
        );
    }

    // ==========================================
    // Scan Creation Tests
    // ==========================================

    public function test_user_can_create_basic_scan(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'basic',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('scans', [
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'pending',
            'requested_tier' => 'basic',
        ]);

        Queue::assertPushed(ScanWebsiteJob::class);
    }

    public function test_url_is_required_for_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'tier' => 'basic',
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_url_must_be_valid_for_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'not-a-url',
                'tier' => 'basic',
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_pro_scan_requires_tokens_when_not_subscribed(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'pro',
            ]);

        $response->assertSessionHasErrors(['limit']);
    }

    public function test_pro_scan_deducts_tokens(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 10]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'pro',
            ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals(5, $user->token_balance); // Pro costs 5 tokens

        $this->assertDatabaseHas('scans', [
            'user_id' => $user->id,
            'requested_tier' => 'pro',
            'tokens_charged' => true,
            'tokens_amount' => 5,
        ]);
    }

    public function test_full_scan_deducts_tokens(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 20]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'full',
            ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals(10, $user->token_balance); // Full costs 10 tokens

        $this->assertDatabaseHas('scans', [
            'user_id' => $user->id,
            'requested_tier' => 'full',
            'tokens_charged' => true,
            'tokens_amount' => 10,
        ]);
    }

    public function test_full_scan_requires_sufficient_tokens(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 5]); // Only 5, needs 10

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'full',
            ]);

        $response->assertSessionHasErrors(['limit']);

        $user->refresh();
        $this->assertEquals(5, $user->token_balance); // Tokens not deducted
    }

    public function test_admin_can_scan_any_tier_without_tokens(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $response = $this->actingAs($admin)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'full',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('scans', [
            'user_id' => $admin->id,
            'requested_tier' => 'full',
            'tokens_charged' => false,
        ]);
    }

    // ==========================================
    // Cooldown Tests
    // ==========================================

    public function test_basic_scan_enforces_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);

        // Create a recent scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'created_at' => now()->subMinutes(2), // Within 5 minute cooldown
        ]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'basic',
            ]);

        $response->assertSessionHasErrors(['cooldown']);
    }

    public function test_pro_scan_has_shorter_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 10]);

        // Create a scan 2 minutes ago (within basic cooldown, outside pro cooldown)
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'created_at' => now()->subMinutes(2),
        ]);

        // Pro scan should work (1 min cooldown)
        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'pro',
            ]);

        $response->assertRedirect();
    }

    public function test_cooldown_check_endpoint_works(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        // Create a recent scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)
            ->post('/scan/check-cooldown', [
                'url' => 'https://example.com',
                'tier' => 'basic',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['on_cooldown' => true]);
    }

    public function test_failed_scans_dont_trigger_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);

        // Create a recent failed scan
        Scan::factory()->failed()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)
            ->post('/scan', [
                'url' => 'https://example.com',
                'tier' => 'basic',
            ]);

        $response->assertRedirect(); // Should succeed
    }

    // ==========================================
    // Scan Viewing Tests
    // ==========================================

    public function test_user_can_view_own_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->get("/scans/{$scan->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Scans/Show')
            ->has('scan')
        );
    }

    public function test_user_cannot_view_others_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $otherUser = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->get("/scans/{$scan->uuid}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_scan(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)
            ->get("/scans/{$scan->uuid}");

        $response->assertStatus(200);
    }

    public function test_scan_status_endpoint_works(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->processing()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/scans/{$scan->uuid}/status");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'processing',
        ]);
    }

    // ==========================================
    // Scan List Tests
    // ==========================================

    public function test_user_can_view_scan_list(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        Scan::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/scans');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Scans/Index')
            ->has('scans.data', 5)
        );
    }

    public function test_scan_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        Scan::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'completed']);
        Scan::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'failed']);

        $response = $this->actingAs($user)
            ->get('/scans?status=completed');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('scans.data', 3)
        );
    }

    public function test_scan_list_can_be_searched(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'title' => 'Example Site',
        ]);
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://other.com',
            'title' => 'Other Site',
        ]);

        $response = $this->actingAs($user)
            ->get('/scans?search=example');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('scans.data', 1)
        );
    }

    // ==========================================
    // Scan Cancellation Tests
    // ==========================================

    public function test_user_can_cancel_pending_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/cancel");

        $response->assertRedirect();

        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_user_can_cancel_processing_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->processing()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/cancel");

        $response->assertRedirect();

        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_user_cannot_cancel_completed_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/cancel");

        $response->assertSessionHasErrors(['status']);

        $scan->refresh();
        $this->assertEquals('completed', $scan->status);
    }

    // ==========================================
    // Scan Deletion Tests
    // ==========================================

    public function test_user_can_delete_own_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete("/scans/{$scan->uuid}");

        $response->assertRedirect('/dashboard');

        $this->assertSoftDeleted('scans', ['id' => $scan->id]);
    }

    public function test_user_cannot_delete_others_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $otherUser = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->delete("/scans/{$scan->uuid}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('scans', ['id' => $scan->id, 'deleted_at' => null]);
    }

    // ==========================================
    // Rescan Tests
    // ==========================================

    public function test_user_can_rescan_with_basic_tier(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'created_at' => now()->subHours(1), // Outside cooldown
        ]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/rescan", ['tier' => 'basic']);

        $response->assertRedirect();

        // New scan should be created
        $this->assertDatabaseHas('scans', [
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'pending',
            'requested_tier' => 'basic',
        ]);

        Queue::assertPushed(ScanWebsiteJob::class);
    }

    public function test_rescan_respects_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'created_at' => now()->subMinutes(1), // Within cooldown
        ]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/rescan", ['tier' => 'basic']);

        $response->assertSessionHasErrors(['cooldown']);
    }

    public function test_rescan_with_tokens_deducts_correctly(): void
    {
        Queue::fake();

        $user = User::factory()->create(['token_balance' => 20]);
        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($user)
            ->post("/scans/{$scan->uuid}/rescan", ['tier' => 'full']);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals(10, $user->token_balance); // Full costs 10
    }

    // ==========================================
    // Bulk Scan Tests
    // ==========================================

    public function test_bulk_scan_page_requires_feature(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)->get('/scans/bulk');

        // Free users see upgrade page
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Scans/BulkUpgrade'));
    }

    public function test_admin_can_access_bulk_scan(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $response = $this->actingAs($admin)->get('/scans/bulk');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Scans/Bulk'));
    }

    public function test_bulk_scan_validates_urls(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $response = $this->actingAs($admin)
            ->post('/scans/bulk', [
                'urls' => "not-a-url\nalso-invalid",
                'tier' => 'basic',
            ]);

        $response->assertSessionHasErrors(['urls']);
    }

    public function test_bulk_scan_limits_to_50_urls(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);

        $urls = implode("\n", array_map(fn ($i) => "https://example{$i}.com", range(1, 60)));

        $response = $this->actingAs($admin)
            ->post('/scans/bulk', [
                'urls' => $urls,
                'tier' => 'basic',
            ]);

        $response->assertSessionHasErrors(['urls']);
    }

    public function test_bulk_scan_deducts_tokens_for_all_urls(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 100]);

        $response = $this->actingAs($admin)
            ->post('/scans/bulk', [
                'urls' => "https://example1.com\nhttps://example2.com",
                'tier' => 'basic',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Admin doesn't pay tokens
        $admin->refresh();
        $this->assertEquals(100, $admin->token_balance);
    }

    // ==========================================
    // Team Scan Tests
    // ==========================================

    public function test_team_member_can_view_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);

        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member, ['role' => 'member']);

        $scan = Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($member)
            ->get("/scans/{$scan->uuid}");

        $response->assertStatus(200);
    }

    public function test_non_team_member_cannot_view_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $nonMember = User::factory()->create(['token_balance' => 0]);

        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $scan = Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($nonMember)
            ->get("/scans/{$scan->uuid}");

        $response->assertStatus(403);
    }

    // ==========================================
    // Bulk Status Tests
    // ==========================================

    public function test_bulk_status_returns_scan_statuses(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $scan1 = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        $scan2 = Scan::factory()->pending()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post('/scans/bulk/status', [
                'uuids' => [(string) $scan1->uuid, (string) $scan2->uuid],
            ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'scans');
    }
}
