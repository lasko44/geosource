<?php

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Store Tests
    // ==========================================

    public function test_owner_can_send_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('teams.invitations.store', $team), [
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect(route('teams.members', $team));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        Notification::assertSentOnDemand(
            TeamInvitationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'newmember@example.com'
        );
    }

    public function test_member_cannot_send_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->post(route('teams.invitations.store', $team), [
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_invite_existing_member(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $existingMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($existingMember->id, ['role' => 'member']);

        $response = $this->actingAs($owner)->post(route('teams.invitations.store', $team), [
            'email' => $existingMember->email,
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_cannot_duplicate_pending_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'test@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post(route('teams.invitations.store', $team), [
            'email' => 'test@example.com',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_only_owner_can_invite_admin(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $admin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('teams.invitations.store', $team), [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors(['role']);
    }

    // ==========================================
    // Destroy Tests
    // ==========================================

    public function test_admin_can_cancel_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->delete(route('teams.invitations.destroy', [$team, $invitation]));

        $response->assertRedirect(route('teams.members', $team));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_cannot_cancel_other_teams_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $otherOwner = User::factory()->create(['is_admin' => true]);
        $otherTeam = Team::factory()->create(['owner_id' => $otherOwner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $otherTeam->id,
            'invited_by' => $otherOwner->id,
        ]);

        $response = $this->actingAs($owner)->delete(route('teams.invitations.destroy', [$team, $invitation]));

        $response->assertStatus(404);
    }

    // ==========================================
    // Resend Tests
    // ==========================================

    public function test_admin_can_resend_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
            'email' => 'resend@example.com',
        ]);
        $originalToken = $invitation->token;

        $response = $this->actingAs($owner)->post(route('teams.invitations.resend', [$team, $invitation]));

        $response->assertRedirect(route('teams.members', $team));
        $response->assertSessionHas('success');

        $invitation->refresh();
        $this->assertNotEquals($originalToken, $invitation->token);

        Notification::assertSentOnDemand(TeamInvitationNotification::class);
    }

    public function test_cannot_resend_accepted_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post(route('teams.invitations.resend', [$team, $invitation]));

        $response->assertSessionHasErrors(['error']);
    }

    // ==========================================
    // Show Tests
    // ==========================================

    public function test_can_view_valid_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('teams.invitations.show', $invitation->token));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/AcceptInvitation'));
    }

    public function test_expired_invitation_shows_error(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('teams.invitations.show', $invitation->token));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/InvitationExpired'));
    }

    public function test_accepted_invitation_shows_error(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('teams.invitations.show', $invitation->token));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('teams/InvitationExpired'));
    }

    // ==========================================
    // Accept Tests
    // ==========================================

    public function test_user_can_accept_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($invitee)->post(route('teams.invitations.accept', $invitation->token));

        $response->assertRedirect(route('teams.show', $team));
        $response->assertSessionHas('success');

        $this->assertTrue($team->hasMember($invitee));
        $invitation->refresh();
        $this->assertTrue($invitation->isAccepted());
    }

    public function test_cannot_accept_with_different_email(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'correct@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($wrongUser)->post(route('teams.invitations.accept', $invitation->token));

        $response->assertSessionHasErrors(['error']);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($invitee)->post(route('teams.invitations.accept', $invitation->token));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['error']);
    }
}
