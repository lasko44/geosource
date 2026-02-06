<?php

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_guest_cannot_view_teams(): void
    {
        $response = $this->get(route('teams.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_teams_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('teams.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/Index'));
    }

    public function test_user_sees_owned_and_member_teams(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $ownedTeam = Team::factory()->create(['owner_id' => $owner->id]);

        $otherOwner = User::factory()->create(['is_admin' => true]);
        $memberTeam = Team::factory()->create(['owner_id' => $otherOwner->id]);
        $memberTeam->members()->attach($owner->id, ['role' => 'member']);

        $response = $this->actingAs($owner)->get(route('teams.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teams/Index')
            ->has('ownedTeams')
            ->has('memberTeams')
        );
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_can_view_create_team_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('teams.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/Create'));
    }

    // ==========================================
    // Store Tests
    // ==========================================

    public function test_user_with_permission_can_create_team(): void
    {
        // Admin users can create teams (unlimited)
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post(route('teams.store'), [
            'name' => 'Test Team',
            'description' => 'A test team description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'owner_id' => $user->id,
        ]);
    }

    public function test_free_user_cannot_create_team(): void
    {
        // Free tier users cannot create teams
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('teams.store'), [
            'name' => 'Test Team',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['team']);
    }

    public function test_team_creation_generates_unique_slug(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        // Create first team
        Team::factory()->create(['slug' => 'test-team']);

        $response = $this->actingAs($user)->post(route('teams.store'), [
            'name' => 'Test Team',
        ]);

        $response->assertRedirect();

        // Slug should be unique with suffix
        $this->assertDatabaseHas('teams', [
            'owner_id' => $user->id,
            'slug' => 'test-team-1',
        ]);
    }

    // ==========================================
    // Show Tests
    // ==========================================

    public function test_team_member_can_view_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('teams.show', $team));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teams/Show')
            ->has('team')
            ->where('isOwner', true)
        );
    }

    public function test_non_member_cannot_view_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonMember)->get(route('teams.show', $team));

        $response->assertStatus(403);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_owner_can_view_edit_form(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('teams.edit', $team));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/Edit'));
    }

    public function test_admin_can_view_edit_form(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('teams.edit', $team));

        $response->assertStatus(200);
    }

    public function test_member_cannot_view_edit_form(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->get(route('teams.edit', $team));

        $response->assertStatus(403);
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->put(route('teams.update', $team), [
            'name' => 'Updated Team Name',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $team->refresh();
        $this->assertEquals('Updated Team Name', $team->name);
    }

    public function test_member_cannot_update_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->put(route('teams.update', $team), [
            'name' => 'Hacked Team Name',
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // Destroy Tests
    // ==========================================

    public function test_owner_can_delete_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->delete(route('teams.destroy', $team));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_admin_cannot_delete_team(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('teams.destroy', $team));

        $response->assertStatus(403);
    }

    // ==========================================
    // Transfer Ownership Tests
    // ==========================================

    public function test_owner_can_transfer_ownership(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $newOwner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($newOwner->id, ['role' => 'admin']);

        $response = $this->actingAs($owner)->post(route('teams.transfer', $team), [
            'user_id' => $newOwner->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $team->refresh();
        $this->assertEquals($newOwner->id, $team->owner_id);
    }

    public function test_cannot_transfer_to_non_member(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $nonMember = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('teams.transfer', $team), [
            'user_id' => $nonMember->id,
        ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_cannot_transfer_to_user_without_team_permission(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        // Free tier user cannot own teams
        $freeUser = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($freeUser->id, ['role' => 'admin']);

        $response = $this->actingAs($owner)->post(route('teams.transfer', $team), [
            'user_id' => $freeUser->id,
        ]);

        $response->assertSessionHasErrors(['user_id']);
    }
}
