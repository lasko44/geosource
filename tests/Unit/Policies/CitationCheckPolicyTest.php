<?php

namespace Tests\Unit\Policies;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Team;
use App\Models\User;
use App\Policies\CitationCheckPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitationCheckPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CitationCheckPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CitationCheckPolicy;
    }

    // ==========================================
    // View Tests
    // ==========================================

    public function test_owner_can_view_own_check(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $query = CitationQuery::factory()->create(['user_id' => $user->id]);
        $check = CitationCheck::factory()->create([
            'user_id' => $user->id,
            'citation_query_id' => $query->id,
        ]);

        $this->assertTrue($this->policy->view($user, $check));
    }

    public function test_admin_can_view_any_check(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $query = CitationQuery::factory()->create(['user_id' => $owner->id]);
        $check = CitationCheck::factory()->create([
            'user_id' => $owner->id,
            'citation_query_id' => $query->id,
        ]);

        $this->assertTrue($this->policy->view($admin, $check));
    }

    public function test_user_cannot_view_others_check(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $otherUser = User::factory()->create();
        $query = CitationQuery::factory()->create(['user_id' => $otherUser->id]);
        $check = CitationCheck::factory()->create([
            'user_id' => $otherUser->id,
            'citation_query_id' => $query->id,
        ]);

        $this->assertFalse($this->policy->view($user, $check));
    }

    public function test_team_member_can_view_team_check(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($member->id, ['role' => 'member']);

        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);
        $check = CitationCheck::factory()->create([
            'user_id' => $owner->id,
            'citation_query_id' => $query->id,
            'team_id' => $team->id,
        ]);

        $this->assertTrue($this->policy->view($member, $check));
    }

    public function test_non_team_member_cannot_view_team_check(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create(['token_balance' => 100]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $query = CitationQuery::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
        ]);
        $check = CitationCheck::factory()->create([
            'user_id' => $owner->id,
            'citation_query_id' => $query->id,
            'team_id' => $team->id,
        ]);

        $this->assertFalse($this->policy->view($nonMember, $check));
    }
}
