<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PricingTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_pricing_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('Pay only for what you use')
                ->assertSee('Free')
                ->assertSee('Pay As You Go')
                ->assertSee('Team');
        });
    }

    public function test_pricing_page_shows_free_tier(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('$0')
                ->assertSee('forever')
                ->assertSee('Unlimited basic scans');
        });
    }

    public function test_pricing_page_shows_token_packages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('Token Packages')
                ->assertSee('50')
                ->assertSee('$5')
                ->assertSee('200')
                ->assertSee('$15')
                ->assertSee('500')
                ->assertSee('$30')
                ->assertSee('1,000')
                ->assertSee('$50');
        });
    }

    public function test_pricing_page_shows_token_costs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('What Do Tokens Cost')
                ->assertSee('Basic Scan')
                ->assertSee('FREE')
                ->assertSee('Pro Scan')
                ->assertSee('5')
                ->assertSee('Full Scan')
                ->assertSee('10');
        });
    }

    public function test_pricing_page_shows_citation_costs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('AI Citations')
                ->assertSee('Perplexity')
                ->assertSee('ChatGPT')
                ->assertSee('Claude')
                ->assertSee('Gemini')
                ->assertSee('DeepSeek');
        });
    }

    public function test_pricing_page_shows_geo_score_pillars(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('GEO Score Pillars')
                ->assertSee('Basic Scan')
                ->assertSee('Pro Scan')
                ->assertSee('Full Scan')
                ->assertSee('Clear Definitions')
                ->assertSee('Structured Knowledge')
                ->assertSee('Topic Authority');
        });
    }

    public function test_pricing_page_shows_team_plan_features(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('$99')
                ->assertSee('month')
                ->assertSee('1,000 tokens')
                ->assertSee('3 teams')
                ->assertSee('White-label reports')
                ->assertSee('GA4');
        });
    }

    public function test_pricing_page_shows_faq(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('Frequently Asked Questions')
                ->assertSee('Do tokens expire')
                ->assertSee('never expire')
                ->assertSee('Can I get a refund')
                ->assertSee('Team plan');
        });
    }

    public function test_pricing_page_navigation_links(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSeeLink('Log in')
                ->assertSeeLink('Get Started')
                ->assertSeeLink('Resources');
        });
    }

    public function test_authenticated_user_sees_dashboard_link(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/pricing')
                ->assertSeeLink('Dashboard');
        });
    }

    public function test_pricing_page_shows_bulk_discount_info(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('Bulk discounts')
                ->assertSee('50%')
                ->assertSee('Save');
        });
    }

    public function test_pricing_page_shows_max_points_for_each_tier(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('100 pts max')
                ->assertSee('135 pts max')
                ->assertSee('175 pts max');
        });
    }

    public function test_pricing_page_cta_buttons_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertPresent('a[href="/"]')  // Try Free Scan
                ->assertPresent('a[href="/register"]');  // Get Started
        });
    }

    public function test_pricing_page_shows_contact_info(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('support@geosource.ai')
                ->assertSee('Need help');
        });
    }

    public function test_pricing_page_has_title(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertTitleContains('Pricing');
        });
    }

    public function test_pricing_page_shows_other_feature_costs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pricing')
                ->assertSee('PDF Export')
                ->assertSee('2 tokens')
                ->assertSee('Scheduled Scan')
                ->assertSee('5 tokens');
        });
    }
}
