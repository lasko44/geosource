<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Log in')
                ->assertSee('Email')
                ->assertSee('Password');
        });
    }

    public function test_guest_can_view_register_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertSee('Create an account')
                ->assertSee('Name')
                ->assertSee('Email')
                ->assertSee('Password');
        });
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertAuthenticated();
        });
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'wrong-password')
                ->press('Log in')
                ->waitForText('These credentials do not match')
                ->assertSee('These credentials do not match');
        });
    }

    public function test_user_can_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('name', 'Test User')
                ->type('email', 'newuser@example.com')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->click('[data-test="register-user-button"]')
                ->waitForText('Verify email', 10)
                ->assertSee('Verify email');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertAuthenticated();

            // Dismiss cookie consent using JavaScript
            $browser->script("localStorage.setItem('geosource_cookie_consent', 'accepted')");
            $browser->refresh()
                ->pause(500);

            // Click the sidebar menu button using JavaScript to avoid overlay issues
            $browser->script("document.querySelector('[data-test=\"sidebar-menu-button\"]').click()");
            $browser->waitFor('[data-test="logout-button"]', 10)
                ->pause(300);

            // Click logout button
            $browser->script("document.querySelector('[data-test=\"logout-button\"]').click()");
            $browser->pause(1000)
                ->assertGuest();
        });
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
