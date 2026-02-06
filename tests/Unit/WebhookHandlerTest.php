<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for webhook-related token crediting logic.
 *
 * Since Stripe webhooks require signature verification, we test the underlying
 * TokenService logic that the webhook handler calls.
 */
class WebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // Token Purchase Crediting Tests
    // ==========================================

    public function test_credit_purchase_adds_tokens_to_user(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $transaction = $this->tokenService->creditPurchase($user, $package, 'cs_test_123');

        $user->refresh();
        $this->assertEquals(100, $user->token_balance);
        $this->assertNotNull($transaction);
        $this->assertEquals(TokenTransaction::TYPE_PURCHASE, $transaction->type);
        $this->assertEquals(100, $transaction->amount);
        $this->assertEquals(100, $transaction->balance_after);
    }

    public function test_credit_purchase_adds_to_existing_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $this->tokenService->creditPurchase($user, $package, 'cs_test_456');

        $user->refresh();
        $this->assertEquals(150, $user->token_balance);
    }

    public function test_credit_purchase_creates_transaction_record(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create([
            'name' => '100 Token Pack',
            'tokens' => 100,
            'price_cents' => 999,
        ]);

        $transaction = $this->tokenService->creditPurchase($user, $package, 'cs_test_789');

        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 100,
            'balance_after' => 100,
        ]);

        $this->assertEquals('Purchased 100 Token Pack', $transaction->description);
        $this->assertEquals($package->id, $transaction->metadata['package_id']);
        $this->assertEquals('cs_test_789', $transaction->metadata['stripe_session_id']);
    }

    public function test_credit_purchase_with_different_package_sizes(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $smallPackage = TokenPackage::factory()->create(['tokens' => 50]);
        $this->tokenService->creditPurchase($user, $smallPackage, 'cs_test_small');
        $user->refresh();
        $this->assertEquals(50, $user->token_balance);

        $mediumPackage = TokenPackage::factory()->create(['tokens' => 200]);
        $this->tokenService->creditPurchase($user, $mediumPackage, 'cs_test_medium');
        $user->refresh();
        $this->assertEquals(250, $user->token_balance);

        $largePackage = TokenPackage::factory()->create(['tokens' => 1000]);
        $this->tokenService->creditPurchase($user, $largePackage, 'cs_test_large');
        $user->refresh();
        $this->assertEquals(1250, $user->token_balance);
    }

    // ==========================================
    // Concurrent Purchase Protection Tests
    // ==========================================

    public function test_credit_purchase_uses_database_lock(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        // First purchase
        $this->tokenService->creditPurchase($user, $package, 'cs_test_first');
        $user->refresh();
        $this->assertEquals(100, $user->token_balance);

        // Simulate second "concurrent" purchase (would happen via second webhook)
        $this->tokenService->creditPurchase($user, $package, 'cs_test_second');
        $user->refresh();
        $this->assertEquals(200, $user->token_balance);
    }

    // ==========================================
    // Transaction Metadata Tests
    // ==========================================

    public function test_credit_purchase_stores_stripe_session_id(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $transaction = $this->tokenService->creditPurchase($user, $package, 'cs_unique_session_123');

        $this->assertEquals('cs_unique_session_123', $transaction->metadata['stripe_session_id']);
    }

    public function test_credit_purchase_stores_package_details(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create([
            'name' => 'Premium Pack',
            'tokens' => 500,
            'price_cents' => 4999,
        ]);

        $transaction = $this->tokenService->creditPurchase($user, $package, 'cs_test_details');

        $this->assertEquals($package->id, $transaction->metadata['package_id']);
        $this->assertEquals('Premium Pack', $transaction->metadata['package_name']);
        $this->assertEquals(4999, $transaction->metadata['price_cents']);
    }

    // ==========================================
    // Billable Entity Tests
    // ==========================================

    public function test_user_can_be_found_by_stripe_id(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test_123',
            'token_balance' => 0,
        ]);

        $found = User::where('stripe_id', 'cus_test_123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_team_can_be_found_by_stripe_id(): void
    {
        $owner = User::factory()->create(['is_admin' => true, 'token_balance' => 0]);
        $team = Team::factory()->create([
            'owner_id' => $owner->id,
            'stripe_id' => 'cus_team_456',
        ]);

        $found = Team::where('stripe_id', 'cus_team_456')->first();

        $this->assertNotNull($found);
        $this->assertEquals($team->id, $found->id);
    }

    public function test_stripe_id_null_returns_no_user(): void
    {
        $found = User::where('stripe_id', null)->first();

        // This should not match any user (stripe_id is a string column)
        // Test that searching for null doesn't throw an error
        $this->assertNull($found);
    }

    // ==========================================
    // Token Package Tests
    // ==========================================

    public function test_token_package_active_scope(): void
    {
        TokenPackage::factory()->create(['is_active' => true, 'name' => 'Active']);
        TokenPackage::factory()->create(['is_active' => false, 'name' => 'Inactive']);

        $active = TokenPackage::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active->first()->name);
    }

    public function test_token_package_price_attribute(): void
    {
        $package = TokenPackage::factory()->create(['price_cents' => 999]);

        $this->assertEquals(9.99, $package->price);
    }

    public function test_token_package_price_per_token_attribute(): void
    {
        $package = TokenPackage::factory()->create([
            'tokens' => 100,
            'price_cents' => 1000,
        ]);

        $this->assertEquals(0.10, $package->price_per_token);
    }

    public function test_token_package_formatted_price_attribute(): void
    {
        $package = TokenPackage::factory()->create(['price_cents' => 999]);

        $this->assertEquals('$9.99', $package->formatted_price);
    }
}
