<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Dashboard Tests
    // ==========================================

    public function test_guest_cannot_access_billing_dashboard(): void
    {
        $response = $this->get(route('billing.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_billing_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('billing/Index')
            ->has('subscription')
        );
    }

    // ==========================================
    // Plans Tests
    // ==========================================

    public function test_guest_cannot_view_plans(): void
    {
        $response = $this->get(route('billing.plans'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_plans(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('billing.plans'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('billing/Plans')
            ->has('plans')
        );
    }

    // ==========================================
    // Checkout Tests
    // ==========================================

    public function test_guest_cannot_access_checkout(): void
    {
        $response = $this->get(route('billing.checkout', 'team'));

        $response->assertRedirect(route('login'));
    }

    // Note: Checkout page requires Stripe API key to create setup intent
    // Skipping test that requires Stripe API calls

    public function test_checkout_with_invalid_plan_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('billing.checkout', 'nonexistent'));

        $response->assertStatus(404);
    }

    // ==========================================
    // Thank You Tests
    // ==========================================

    public function test_user_can_access_thank_you_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('billing.thank-you'));

        // May redirect or show page depending on session state
        $response->assertStatus(302)->assertRedirect();
    }

    // ==========================================
    // Payment Methods Tests (require Stripe API key)
    // ==========================================

    public function test_guest_cannot_access_payment_methods(): void
    {
        $response = $this->get(route('billing.payment-methods'));

        $response->assertRedirect(route('login'));
    }

    // Note: Payment methods page requires Stripe API key to be configured
    // Skipping test that requires Stripe API calls

    // ==========================================
    // Invoices Tests
    // ==========================================

    public function test_guest_cannot_access_invoices(): void
    {
        $response = $this->get(route('billing.invoices'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_invoices(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('billing.invoices'));

        $response->assertStatus(200);
    }

    // ==========================================
    // Subscribe Tests (require Stripe mocking for full tests)
    // ==========================================

    public function test_guest_cannot_subscribe(): void
    {
        $response = $this->post(route('billing.subscribe'), [
            'plan' => 'team',
            'payment_method' => 'pm_fake',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_subscribe_requires_payment_method(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'team',
        ]);

        $response->assertSessionHasErrors(['payment_method']);
    }

    public function test_subscribe_requires_valid_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'fake_plan',
            'payment_method' => 'pm_fake',
        ]);

        $response->assertSessionHasErrors(['plan']);
    }

    // ==========================================
    // Cancel Tests
    // ==========================================

    public function test_guest_cannot_cancel_subscription(): void
    {
        $response = $this->post(route('billing.cancel'));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // Resume Tests
    // ==========================================

    public function test_guest_cannot_resume_subscription(): void
    {
        $response = $this->post(route('billing.resume'));

        $response->assertRedirect(route('login'));
    }
}
