<?php

namespace Tests\Feature\Scans;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanOperationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Scan Status Tests
    // ==========================================

    public function test_guest_cannot_check_scan_status(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson(route('scans.status', $scan));

        $response->assertStatus(401);
    }

    public function test_owner_can_check_scan_status(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('scans.status', $scan));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'progress_percent',
        ]);
    }

    public function test_non_owner_cannot_check_scan_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scan = Scan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson(route('scans.status', $scan));

        $response->assertStatus(403);
    }

    // ==========================================
    // Scan Cancel Tests
    // ==========================================

    public function test_owner_can_cancel_pending_scan(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('scans.cancel', $scan));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'cancelled',
        ]);

        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_owner_can_cancel_processing_scan(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->processing()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('scans.cancel', $scan));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $scan->refresh();
        $this->assertEquals('cancelled', $scan->status);
    }

    public function test_cannot_cancel_completed_scan(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user)->postJson(route('scans.cancel', $scan));

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    public function test_non_owner_cannot_cancel_scan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scan = Scan::factory()->pending()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->postJson(route('scans.cancel', $scan));

        $response->assertStatus(403);
    }

    // ==========================================
    // Check Cooldown Tests
    // ==========================================

    public function test_guest_cannot_check_cooldown(): void
    {
        $response = $this->postJson(route('scan.check-cooldown'), [
            'url' => 'https://example.com',
            'tier' => 'basic',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_check_cooldown(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('scan.check-cooldown'), [
            'url' => 'https://example.com',
            'tier' => 'basic',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'on_cooldown',
        ]);
    }

    public function test_cooldown_returns_true_for_recently_scanned_url(): void
    {
        $user = User::factory()->create();

        // Create a recent scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'completed_at' => now()->subMinutes(5), // 5 minutes ago
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.check-cooldown'), [
            'url' => 'https://example.com',
            'tier' => 'basic',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['on_cooldown' => true]);
    }
}
