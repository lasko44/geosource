<?php

namespace Tests\Browser;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScanTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_homepage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('GEO')
                ->assertSee('AI');
        });
    }

    public function test_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }

    public function test_dashboard_shows_scan_form(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Start a GEO Scan')
                ->assertPresent('input[type="url"]')
                ->assertSee('Basic')
                ->assertSee('Pro')
                ->assertSee('Full');
        });
    }

    public function test_pro_scan_requires_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Pro')
                ->assertSee('5 tokens');
        });
    }

    public function test_user_can_view_completed_scan(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'score' => 75.5,
            'grade' => 'B',
        ]);

        $this->browse(function (Browser $browser) use ($user, $scan) {
            $browser->loginAs($user)
                ->visit("/scans/{$scan->uuid}")
                ->assertSee('example.com')
                ->assertSee('B');
        });
    }

    public function test_user_can_view_scan_history(): void
    {
        $user = User::factory()->create();

        Scan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/scans')
                ->assertSee('Scan');
        });
    }

    public function test_scan_shows_pillars_on_completed_scan(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'requested_tier' => 'basic',
            'results' => [
                'pillars' => [
                    'definitions' => [
                        'name' => 'Clear Definitions',
                        'score' => 18,
                        'max_score' => 20,
                        'percentage' => 90,
                        'tier' => 'free',
                        'details' => [],
                        'breakdown' => [],
                    ],
                    'structure' => [
                        'name' => 'Structured Knowledge',
                        'score' => 16,
                        'max_score' => 20,
                        'percentage' => 80,
                        'tier' => 'free',
                        'details' => [],
                        'breakdown' => [],
                    ],
                    'authority' => [
                        'name' => 'Topic Authority',
                        'score' => 20,
                        'max_score' => 25,
                        'percentage' => 80,
                        'tier' => 'free',
                        'details' => [],
                        'breakdown' => [],
                    ],
                ],
                'recommendations' => [],
                'summary' => [],
                'max_score' => 100,
            ],
        ]);

        $this->browse(function (Browser $browser) use ($user, $scan) {
            $browser->loginAs($user)
                ->visit("/scans/{$scan->uuid}")
                ->assertSee('Clear Definitions');
        });
    }

    public function test_recent_scans_shown_on_dashboard(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://test-site.com',
            'status' => 'completed',
            'score' => 80,
            'grade' => 'B+',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Recent Scans')
                ->assertSee('test-site.com');
        });
    }

    public function test_empty_state_shown_when_no_scans(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('No scans yet');
        });
    }

    public function test_completed_scan_shows_rescan_option(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->browse(function (Browser $browser) use ($user, $scan) {
            $browser->loginAs($user)
                ->visit("/scans/{$scan->uuid}")
                ->assertSee('Rescan');
        });
    }
}
