<?php

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_member_can_view_members_list(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->get(route('teams.members', $team));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teams/Members')
            ->has('team')
            ->has('members')
            ->has('seats')
        );
    }

    public function test_non_member_cannot_view_members_list(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonMember)->get(route('teams.members', $team));

        $response->assertStatus(403);
    }

    // Note: Members are added through team invitations, not direct store
    // See TeamInvitationControllerTest for invitation tests

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_member_role(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($owner)->put(route('teams.members.update', [$team, $member]), [
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('teams.members', $team));
        $response->assertSessionHas('success');

        $this->assertEquals('admin', $team->getUserRole($member));
    }

    public function test_cannot_change_owner_role(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->put(route('teams.members.update', [$team, $owner]), [
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_only_owner_can_promote_to_admin(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($admin)->put(route('teams.members.update', [$team, $member]), [
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors(['role']);
    }

    // ==========================================
    // Destroy Tests
    // ==========================================

    public function test_admin_can_remove_member(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($admin)->delete(route('teams.members.destroy', [$team, $member]));

        $response->assertRedirect(route('teams.members', $team));
        $response->assertSessionHas('success');

        $this->assertFalse($team->hasMember($member));
    }

    public function test_cannot_remove_owner(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('teams.members.destroy', [$team, $owner]));

        $response->assertSessionHasErrors(['error']);
    }

    // ==========================================
    // Leave Tests
    // ==========================================

    public function test_member_can_leave_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->post(route('teams.leave', $team));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($team->hasMember($member));
    }

    public function test_owner_cannot_leave_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('teams.leave', $team));

        $response->assertSessionHasErrors(['error']);
    }

    public function test_non_member_cannot_leave_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonMember)->post(route('teams.leave', $team));

        $response->assertSessionHasErrors(['error']);
    }
}
