<?php

namespace Tests\Unit\Policies;

use App\Models\ScheduledScan;
use App\Models\Team;
use App\Models\User;
use App\Policies\ScheduledScanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledScanPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ScheduledScanPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ScheduledScanPolicy;
    }

    // ==========================================
    // View Tests
    // ==========================================

    public function test_owner_can_view_own_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $scheduledScan));
    }

    public function test_user_cannot_view_others_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->view($user, $scheduledScan));
    }

    public function test_team_member_can_view_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->view($member, $scheduledScan));
    }

    public function test_non_team_member_cannot_view_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->view($nonMember, $scheduledScan));
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_own_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $scheduledScan));
    }

    public function test_user_cannot_update_others_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->update($user, $scheduledScan));
    }

    public function test_team_admin_can_update_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->update($admin, $scheduledScan));
    }

    public function test_team_member_cannot_update_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->update($member, $scheduledScan));
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_owner_can_delete_own_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $scheduledScan));
    }

    public function test_user_cannot_delete_others_scheduled_scan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $scheduledScan = ScheduledScan::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->delete($user, $scheduledScan));
    }

    public function test_team_admin_can_delete_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->delete($admin, $scheduledScan));
    }

    public function test_team_member_cannot_delete_team_scheduled_scan(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->delete($member, $scheduledScan));
    }
}
