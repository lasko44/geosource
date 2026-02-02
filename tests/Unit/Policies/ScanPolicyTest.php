<?php

namespace Tests\Unit\Policies;

use App\Models\Scan;
use App\Models\Team;
use App\Models\User;
use App\Policies\ScanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ScanPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ScanPolicy();
    }

    // ==========================================
    // viewAny Tests
    // ==========================================

    public function test_any_authenticated_user_can_view_any(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    // ==========================================
    // view Tests
    // ==========================================

    public function test_user_can_view_own_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $scan));
    }

    public function test_user_cannot_view_other_users_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $otherUser = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->view($user, $scan));
    }

    public function test_admin_can_view_any_scan(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($admin, $scan));
    }

    public function test_team_member_can_view_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member, ['role' => 'member']);

        $scan = Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->view($member, $scan));
    }

    public function test_non_team_member_cannot_view_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $nonMember = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $scan = Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->view($nonMember, $scan));
    }

    // ==========================================
    // update Tests
    // ==========================================

    public function test_user_can_update_own_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $scan));
    }

    public function test_user_cannot_update_other_users_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $otherUser = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->update($user, $scan));
    }

    public function test_team_member_can_update_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member, ['role' => 'member']);

        $scan = Scan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->update($member, $scan));
    }

    // ==========================================
    // delete Tests
    // ==========================================

    public function test_user_can_delete_own_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $scan));
    }

    public function test_user_cannot_delete_other_users_scan(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $otherUser = User::factory()->create(['token_balance' => 0]);
        $scan = Scan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->delete($user, $scan));
    }

    public function test_team_owner_can_delete_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member, ['role' => 'member']);

        // Scan created by member
        $scan = Scan::factory()->create([
            'user_id' => $member->id,
            'team_id' => $team->id,
        ]);

        // Owner should be able to delete
        $this->assertTrue($this->policy->delete($owner, $scan));
    }

    public function test_team_admin_can_delete_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $admin = User::factory()->create(['token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin, ['role' => 'admin']);
        $team->members()->attach($member, ['role' => 'member']);

        // Scan created by member
        $scan = Scan::factory()->create([
            'user_id' => $member->id,
            'team_id' => $team->id,
        ]);

        // Team admin should be able to delete
        $this->assertTrue($this->policy->delete($admin, $scan));
    }

    public function test_regular_team_member_cannot_delete_others_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member1 = User::factory()->create(['token_balance' => 0]);
        $member2 = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member1, ['role' => 'member']);
        $team->members()->attach($member2, ['role' => 'member']);

        // Scan created by member1
        $scan = Scan::factory()->create([
            'user_id' => $member1->id,
            'team_id' => $team->id,
        ]);

        // member2 should NOT be able to delete member1's scan
        $this->assertFalse($this->policy->delete($member2, $scan));
    }

    public function test_team_member_can_delete_own_team_scan(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $member = User::factory()->create(['token_balance' => 0]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member, ['role' => 'member']);

        // Scan created by member
        $scan = Scan::factory()->create([
            'user_id' => $member->id,
            'team_id' => $team->id,
        ]);

        // member should be able to delete their own scan
        $this->assertTrue($this->policy->delete($member, $scan));
    }
}
