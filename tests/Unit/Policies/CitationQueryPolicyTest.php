<?php

namespace Tests\Unit\Policies;

use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Policies\CitationQueryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitationQueryPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CitationQueryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(CitationQueryPolicy::class);
    }

    // ==========================================
    // View Any Tests
    // ==========================================

    public function test_admin_can_view_any(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_tokens_can_view_any(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_without_tokens_cannot_view_any(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    // ==========================================
    // View Tests
    // ==========================================

    public function test_owner_can_view_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $query));
    }

    public function test_admin_can_view_any_query(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $query = CitationQuery::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($admin, $query));
    }

    public function test_user_cannot_view_others_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $otherUser = User::factory()->create();
        $query = CitationQuery::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->view($user, $query));
    }

    public function test_team_member_can_view_team_query(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->view($member, $query));
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_admin_can_create(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_with_tokens_can_create(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_tokens_cannot_create(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertFalse($this->policy->create($user));
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_owner_can_update_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $query));
    }

    public function test_user_cannot_update_others_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $otherUser = User::factory()->create();
        $query = CitationQuery::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->update($user, $query));
    }

    public function test_admin_cannot_update_others_query(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $query = CitationQuery::factory()->create(['user_id' => $owner->id]);

        // Only the owner can update, not even admin
        $this->assertFalse($this->policy->update($admin, $query));
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_owner_can_delete_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $query));
    }

    public function test_user_cannot_delete_others_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $otherUser = User::factory()->create();
        $query = CitationQuery::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->delete($user, $query));
    }

    // ==========================================
    // Run Check Tests
    // ==========================================

    public function test_owner_can_run_check(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->runCheck($user, $query));
    }

    public function test_team_member_can_run_check_on_team_query(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->runCheck($member, $query));
    }

    public function test_non_member_cannot_run_check_on_team_query(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->runCheck($nonMember, $query));
    }
}
