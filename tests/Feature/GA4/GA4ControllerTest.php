<?php

namespace Tests\Feature\GA4;

use App\Models\GA4Connection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GA4ControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_guest_cannot_view_ga4_connections(): void
    {
        $response = $this->get(route('citations.ga4.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_with_tokens_can_view_ga4_connections(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)->get(route('citations.ga4.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_view_ga4_connections(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get(route('citations.ga4.index'));

        $response->assertStatus(200);
    }

    // ==========================================
    // Connect Tests
    // ==========================================

    public function test_guest_cannot_access_ga4_connect(): void
    {
        $response = $this->get(route('citations.ga4.connect'));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // Team Context Tests
    // ==========================================

    public function test_team_member_can_view_team_ga4_connection(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $response = $this->actingAs($member)->get(route('citations.ga4.referrals', $connection));

        $response->assertStatus(200);
    }

    public function test_non_team_member_cannot_view_team_ga4_connection(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $nonMember = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $response = $this->actingAs($nonMember)->get(route('citations.ga4.referrals', $connection));

        $response->assertStatus(403);
    }

    // ==========================================
    // Sync Status Tests
    // ==========================================

    public function test_owner_can_check_sync_status(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('citations.ga4.sync-status', $connection));

        $response->assertStatus(200);
        $response->assertJsonStructure(['sync_status']);
    }
}
