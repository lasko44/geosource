<?php

namespace Tests\Browser;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TeamTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_view_teams_list(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/teams')
                ->assertSee('Teams');
        });
    }

    public function test_team_owner_can_view_team_details(): void
    {
        $user = User::factory()->create();

        $team = Team::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Owner Team',
            'slug' => 'owner-team',
        ]);

        $this->browse(function (Browser $browser) use ($user, $team) {
            $browser->loginAs($user)
                ->visit("/teams/{$team->slug}")
                ->assertSee('Owner Team');
        });
    }

    public function test_team_displays_member_count(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $team = Team::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Team with Members',
            'slug' => 'team-with-members',
        ]);

        $team->members()->attach($member1->id, ['role' => 'member']);
        $team->members()->attach($member2->id, ['role' => 'member']);

        $this->browse(function (Browser $browser) use ($owner, $team) {
            $browser->loginAs($owner)
                ->visit("/teams/{$team->slug}")
                ->assertSee('3'); // Owner + 2 members
        });
    }

    public function test_user_shows_owned_and_member_teams(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownedTeam = Team::factory()->create([
            'owner_id' => $user->id,
            'name' => 'My Owned Team',
            'slug' => 'my-owned-team',
        ]);

        $memberTeam = Team::factory()->create([
            'owner_id' => $otherOwner->id,
            'name' => 'Team I Belong To',
            'slug' => 'team-i-belong-to',
        ]);
        $memberTeam->members()->attach($user->id, ['role' => 'member']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/teams')
                ->assertSee('My Owned Team')
                ->assertSee('Team I Belong To');
        });
    }

    public function test_white_label_settings_displayed_for_eligible_teams(): void
    {
        $user = User::factory()->create();

        $team = Team::factory()->withWhiteLabel()->create([
            'owner_id' => $user->id,
            'name' => 'White Label Team',
            'slug' => 'white-label-team',
        ]);

        $this->browse(function (Browser $browser) use ($user, $team) {
            $browser->loginAs($user)
                ->visit("/teams/{$team->slug}")
                ->assertSee('White Label Team');
        });
    }
}
