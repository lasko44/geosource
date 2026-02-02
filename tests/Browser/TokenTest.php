<?php

namespace Tests\Browser;

use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TokenTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_view_token_purchase_page(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens')
                ->assertSee('Tokens');
        });
    }

    public function test_token_packages_show_on_page(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens')
                ->assertDontSee('403');
        });
    }

    public function test_user_can_see_token_balance_in_header(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('100');
        });
    }

    public function test_code_redemption_form_is_visible(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens')
                ->assertSee('Have a Code')
                ->assertPresent('#code')
                ->assertSee('Redeem Code');
        });
    }

    public function test_user_can_view_token_history(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
            'balance_after' => 100,
            'description' => '100 tokens purchased',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens/history')
                ->assertSee('Token History')
                ->assertSee('100');
        });
    }

    public function test_what_tokens_can_do_section_visible(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens')
                ->assertSee('What Can You Do With Tokens')
                ->assertSee('Pro Scan')
                ->assertSee('Full Scan')
                ->assertSee('PDF Export');
        });
    }

    public function test_basic_scans_shown_as_free(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tokens')
                ->assertSee('FREE');
        });
    }
}
