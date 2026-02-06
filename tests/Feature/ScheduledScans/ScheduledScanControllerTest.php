<?php

namespace Tests\Feature\ScheduledScans;

use App\Models\ScheduledScan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledScanControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_guest_cannot_view_scheduled_scans(): void
    {
        $response = $this->get(route('scheduled-scans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_feature_sees_upgrade_page(): void
    {
        // Free tier users don't have scheduled scans feature
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('scheduled-scans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ScheduledScans/Upgrade'));
    }

    public function test_user_with_feature_can_view_scheduled_scans(): void
    {
        // Admin users have all features
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get(route('scheduled-scans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ScheduledScans/Index')
            ->has('scheduledScans')
        );
    }

    public function test_user_only_sees_own_scheduled_scans(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create(['is_admin' => true]);

        // Create scans for both users
        ScheduledScan::factory()->count(2)->create(['user_id' => $user->id]);
        ScheduledScan::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('scheduled-scans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('scheduledScans', 2) // Only user's own scans
        );
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_without_feature_cannot_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('scheduled-scans.create'));

        $response->assertRedirect(route('scheduled-scans.index'));
    }

    public function test_user_with_feature_can_view_create_form(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get(route('scheduled-scans.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ScheduledScans/Create'));
    }

    // ==========================================
    // Store Tests
    // ==========================================

    public function test_user_without_feature_cannot_create_scheduled_scan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('scheduled-scans.store'), [
            'url' => 'https://example.com',
            'name' => 'Test Scan',
            'frequency' => 'daily',
            'scheduled_time' => '09:00',
        ]);

        $response->assertSessionHasErrors(['feature']);
    }

    public function test_user_with_feature_can_create_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post(route('scheduled-scans.store'), [
            'url' => 'https://example.com',
            'name' => 'Test Scan',
            'frequency' => 'daily',
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('scheduled-scans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('scheduled_scans', [
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'frequency' => 'daily',
        ]);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_owner_can_view_edit_form(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('scheduled-scans.edit', $scan));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ScheduledScans/Edit')
            ->has('scheduledScan')
        );
    }

    public function test_non_owner_cannot_view_edit_form(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('scheduled-scans.edit', $scan));

        $response->assertStatus(403);
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('scheduled-scans.update', $scan), [
            'url' => 'https://updated.com',
            'name' => 'Updated Scan',
            'frequency' => 'weekly',
            'day_of_week' => 1,
            'scheduled_time' => '10:00',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('scheduled-scans.index'));
        $response->assertSessionHas('success');

        $scan->refresh();
        $this->assertEquals('https://updated.com', $scan->url);
        $this->assertEquals('weekly', $scan->frequency);
    }

    public function test_non_owner_cannot_update_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('scheduled-scans.update', $scan), [
            'url' => 'https://hacked.com',
            'frequency' => 'daily',
            'scheduled_time' => '10:00',
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // Toggle Tests
    // ==========================================

    public function test_owner_can_toggle_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('scheduled-scans.toggle', $scan));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $scan->refresh();
        $this->assertFalse($scan->is_active);
    }

    // ==========================================
    // Destroy Tests
    // ==========================================

    public function test_owner_can_delete_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('scheduled-scans.destroy', $scan));

        $response->assertRedirect(route('scheduled-scans.index'));
        $response->assertSessionHas('success');

        // ScheduledScan uses soft deletes
        $this->assertSoftDeleted('scheduled_scans', ['id' => $scan->id]);
    }

    public function test_non_owner_cannot_delete_scheduled_scan(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create(['is_admin' => true]);
        $scan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('scheduled-scans.destroy', $scan));

        $response->assertStatus(403);
    }

    // ==========================================
    // Team Context Tests
    // ==========================================

    public function test_team_admin_can_view_team_scheduled_scans(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        // Create team scheduled scan
        $scan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        // Set team context
        session(['current_team_id' => $team->id]);

        $response = $this->actingAs($admin)->get(route('scheduled-scans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('scheduledScans', 1)
        );
    }
}
