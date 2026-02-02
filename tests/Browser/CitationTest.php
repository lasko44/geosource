<?php

namespace Tests\Browser;

use App\Models\CitationQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CitationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_without_subscription_sees_upgrade_page(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/citations')
                ->assertSee('Upgrade');
        });
    }

    public function test_user_with_tokens_can_access_citations_page(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/citations')
                ->assertDontSee('403');
        });
    }

    public function test_user_can_view_citation_query_details(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
            'query' => 'What is the best SEO tool?',
            'domain' => 'example.com',
        ]);

        $this->browse(function (Browser $browser) use ($user, $query) {
            $browser->loginAs($user)
                ->visit("/citations/queries/{$query->uuid}")
                ->assertSee('What is the best SEO tool?');
        });
    }

    public function test_citation_trends_page_accessible(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/citations/trends')
                ->assertDontSee('403');
        });
    }

    public function test_citation_alerts_page_accessible(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/citations/alerts')
                ->assertDontSee('403');
        });
    }
}
