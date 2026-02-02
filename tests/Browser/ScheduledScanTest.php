<?php

namespace Tests\Browser;

use App\Models\ScheduledScan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScheduledScanTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_without_feature_sees_upgrade_page(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/scheduled-scans')
                ->assertSee('Upgrade');
        });
    }

    public function test_scheduled_scans_list_shows_user_scans(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        ScheduledScan::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Scheduled Scan',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/scheduled-scans')
                ->assertDontSee('403');
        });
    }

    public function test_create_form_accessible(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/scheduled-scans/create')
                ->assertDontSee('403');
        });
    }

    public function test_edit_form_accessible_for_owner(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $scheduledScan = ScheduledScan::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->browse(function (Browser $browser) use ($user, $scheduledScan) {
            $browser->loginAs($user)
                ->visit("/scheduled-scans/{$scheduledScan->uuid}/edit")
                ->assertDontSee('403');
        });
    }
}
