<?php

namespace Tests\Unit\Policies;

use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TeamPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TeamPolicy;
    }

    // ==========================================
    // View Tests
    // ==========================================

    public function test_owner_can_view_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $team));
    }

    public function test_member_can_view_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $this->assertTrue($this->policy->view($member, $team));
    }

    public function test_non_member_cannot_view_team(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->policy->view($nonMember, $team));
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $team));
    }

    public function test_admin_can_update_team(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $this->assertTrue($this->policy->update($admin, $team));
    }

    public function test_regular_member_cannot_update_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $this->assertFalse($this->policy->update($member, $team));
    }

    public function test_non_member_cannot_update_team(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->policy->update($nonMember, $team));
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_owner_can_delete_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $team));
    }

    public function test_admin_cannot_delete_team(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $this->assertFalse($this->policy->delete($admin, $team));
    }

    public function test_member_cannot_delete_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $this->assertFalse($this->policy->delete($member, $team));
    }

    // ==========================================
    // Manage Billing Tests
    // ==========================================

    public function test_owner_can_manage_billing(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->manageBilling($user, $team));
    }

    public function test_admin_cannot_manage_billing(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $this->assertFalse($this->policy->manageBilling($admin, $team));
    }

    // ==========================================
    // Manage Members Tests
    // ==========================================

    public function test_owner_can_manage_members(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->manageMembers($user, $team));
    }

    public function test_admin_can_manage_members(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $this->assertTrue($this->policy->manageMembers($admin, $team));
    }

    public function test_member_cannot_manage_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $this->assertFalse($this->policy->manageMembers($member, $team));
    }

    // ==========================================
    // Invite Members Tests
    // ==========================================

    public function test_owner_can_invite_members(): void
    {
        // Owner needs is_admin to have unlimited seats (free tier has 0 seats)
        $user = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($this->policy->inviteMembers($user, $team));
    }

    public function test_admin_can_invite_members(): void
    {
        // Owner needs is_admin to have unlimited seats (free tier has 0 seats)
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $this->assertTrue($this->policy->inviteMembers($admin, $team));
    }

    public function test_member_cannot_invite_members(): void
    {
        // Owner needs is_admin to have unlimited seats (free tier has 0 seats)
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $this->assertFalse($this->policy->inviteMembers($member, $team));
    }

    public function test_cannot_invite_when_over_seat_limit(): void
    {
        // Free tier owner has 0 team member seats
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        // Admin would normally be able to invite, but team is over seat limit
        $this->assertFalse($this->policy->inviteMembers($admin, $team));
    }
}
