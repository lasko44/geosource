<?php

namespace Tests\Unit\Policies;

use App\Models\GA4Connection;
use App\Models\Team;
use App\Models\User;
use App\Policies\GA4ConnectionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GA4ConnectionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected GA4ConnectionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GA4ConnectionPolicy;
    }

    // ==========================================
    // View Any Tests
    // ==========================================

    public function test_any_user_can_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    // ==========================================
    // View Tests
    // ==========================================

    public function test_owner_can_view_own_connection(): void
    {
        $user = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $connection));
    }

    public function test_admin_can_view_any_connection(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $connection = GA4Connection::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($admin, $connection));
    }

    public function test_user_cannot_view_others_connection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->view($user, $connection));
    }

    public function test_team_member_can_view_team_connection(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->view($member, $connection));
    }

    public function test_non_team_member_cannot_view_team_connection(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->view($nonMember, $connection));
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_any_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_own_connection(): void
    {
        $user = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $connection));
    }

    public function test_admin_can_update_any_connection(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $connection = GA4Connection::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->update($admin, $connection));
    }

    public function test_user_cannot_update_others_connection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->update($user, $connection));
    }

    public function test_team_member_can_update_team_connection(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->update($member, $connection));
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_owner_can_delete_own_connection(): void
    {
        $user = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $connection));
    }

    public function test_admin_can_delete_any_connection(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $connection = GA4Connection::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($admin, $connection));
    }

    public function test_user_cannot_delete_others_connection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->delete($user, $connection));
    }

    public function test_team_member_cannot_delete_team_connection(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        // Team members can view/update but not delete
        $this->assertFalse($this->policy->delete($member, $connection));
    }

    // ==========================================
    // Sync Tests
    // ==========================================

    public function test_owner_can_sync_own_connection(): void
    {
        $user = User::factory()->create();
        $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->sync($user, $connection));
    }

    public function test_team_member_can_sync_team_connection(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->sync($member, $connection));
    }

    public function test_non_member_cannot_sync_team_connection(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $connection = GA4Connection::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->sync($nonMember, $connection));
    }
}
