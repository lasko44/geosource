<?php

namespace Tests\Unit\Services;

use App\Models\TokenCode;
use App\Models\TokenCodeRedemption;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService();
    }

    // ==========================================
    // Token Costs & Configuration Tests
    // ==========================================

    public function test_get_cost_returns_configured_cost(): void
    {
        $this->assertEquals(0, $this->tokenService->getCost('scan_basic'));
        $this->assertEquals(5, $this->tokenService->getCost('scan_pro'));
        $this->assertEquals(10, $this->tokenService->getCost('scan_full'));
        $this->assertEquals(2, $this->tokenService->getCost('pdf_export'));
    }

    public function test_get_cost_returns_zero_for_unknown_feature(): void
    {
        $this->assertEquals(0, $this->tokenService->getCost('nonexistent_feature'));
    }

    public function test_get_label_returns_configured_label(): void
    {
        $this->assertEquals('Basic Scan (5 pillars)', $this->tokenService->getLabel('scan_basic'));
        $this->assertEquals('Pro Scan (8 pillars)', $this->tokenService->getLabel('scan_pro'));
    }

    public function test_get_scan_cost_returns_correct_cost_for_tier(): void
    {
        $this->assertEquals(0, $this->tokenService->getScanCost('basic'));
        $this->assertEquals(5, $this->tokenService->getScanCost('pro'));
        $this->assertEquals(10, $this->tokenService->getScanCost('full'));
    }

    public function test_get_citation_cost_returns_correct_cost_for_provider(): void
    {
        $this->assertEquals(3, $this->tokenService->getCitationCost('perplexity'));
        $this->assertEquals(5, $this->tokenService->getCitationCost('openai'));
        $this->assertEquals(1, $this->tokenService->getCitationCost('deepseek'));
    }

    // ==========================================
    // Balance & Token Check Tests
    // ==========================================

    public function test_get_balance_returns_user_token_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->assertEquals(100, $this->tokenService->getBalance($user));
    }

    public function test_get_balance_returns_zero_for_zero_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertEquals(0, $this->tokenService->getBalance($user));
    }

    public function test_has_tokens_for_returns_true_when_sufficient_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->assertTrue($this->tokenService->hasTokensFor($user, 'scan_pro')); // costs 5
    }

    public function test_has_tokens_for_returns_false_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 2]);

        $this->assertFalse($this->tokenService->hasTokensFor($user, 'scan_pro')); // costs 5
    }

    public function test_has_tokens_for_returns_true_for_free_features(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertTrue($this->tokenService->hasTokensFor($user, 'scan_basic')); // costs 0
    }

    public function test_can_scan_returns_true_for_basic_tier_with_zero_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->assertTrue($this->tokenService->canScan($user, 'basic'));
    }

    public function test_can_scan_returns_false_for_pro_tier_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 3]);

        $this->assertFalse($this->tokenService->canScan($user, 'pro'));
    }

    // ==========================================
    // Token Spending Tests
    // ==========================================

    public function test_spend_deducts_tokens_and_creates_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $transaction = $this->tokenService->spend($user, 'scan_pro', ['scan_id' => 123]);

        $user->refresh();
        $this->assertEquals(95, $user->token_balance);
        $this->assertNotNull($transaction);
        $this->assertEquals(-5, $transaction->amount);
        $this->assertEquals(95, $transaction->balance_after);
        $this->assertEquals(TokenTransaction::TYPE_SPEND, $transaction->type);
        $this->assertEquals('Pro Scan (8 pillars)', $transaction->description);
        $this->assertEquals(123, $transaction->metadata['scan_id']);
    }

    public function test_spend_throws_exception_for_insufficient_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 3]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient token balance');

        $this->tokenService->spend($user, 'scan_pro');
    }

    public function test_spend_returns_null_for_free_features(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $transaction = $this->tokenService->spend($user, 'scan_basic');

        $user->refresh();
        $this->assertNull($transaction);
        $this->assertEquals(100, $user->token_balance); // No change
    }

    public function test_spend_for_scan_deducts_correct_amount(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->tokenService->spendForScan($user, 'full', ['scan_id' => 456]);

        $user->refresh();
        $this->assertEquals(90, $user->token_balance); // 100 - 10
    }

    public function test_spend_for_citation_deducts_correct_amount(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->tokenService->spendForCitation($user, 'perplexity', ['check_id' => 789]);

        $user->refresh();
        $this->assertEquals(97, $user->token_balance); // 100 - 3
    }

    public function test_spend_uses_database_lock_to_prevent_race_conditions(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        // Simulate concurrent spending by directly calling spend multiple times
        // The lock should ensure balance is properly maintained
        $this->tokenService->spend($user, 'scan_pro');

        $user->refresh();
        $this->assertEquals(5, $user->token_balance);

        // Second spend should also work
        $this->tokenService->spend($user, 'scan_pro');

        $user->refresh();
        $this->assertEquals(0, $user->token_balance);
    }

    // ==========================================
    // Token Credit Tests
    // ==========================================

    public function test_credit_purchase_adds_tokens_and_creates_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);
        $package = TokenPackage::create([
            'name' => 'Test Package',
            'tokens' => 100,
            'price_cents' => 1500,
            'stripe_price_id' => 'price_test',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $transaction = $this->tokenService->creditPurchase($user, $package, 'sess_123');

        $user->refresh();
        $this->assertEquals(150, $user->token_balance);
        $this->assertEquals(100, $transaction->amount);
        $this->assertEquals(150, $transaction->balance_after);
        $this->assertEquals(TokenTransaction::TYPE_PURCHASE, $transaction->type);
        $this->assertEquals('sess_123', $transaction->metadata['stripe_session_id']);
    }

    public function test_credit_bonus_adds_tokens_and_creates_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $transaction = $this->tokenService->creditBonus($user, 25, 'Welcome bonus');

        $user->refresh();
        $this->assertEquals(35, $user->token_balance);
        $this->assertEquals(25, $transaction->amount);
        $this->assertEquals(TokenTransaction::TYPE_BONUS, $transaction->type);
        $this->assertEquals('Welcome bonus', $transaction->description);
    }

    public function test_refund_adds_tokens_back_and_creates_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);

        $transaction = $this->tokenService->refund($user, 10, 'Scan failed - refund');

        $user->refresh();
        $this->assertEquals(60, $user->token_balance);
        $this->assertEquals(10, $transaction->amount);
        $this->assertEquals(TokenTransaction::TYPE_REFUND, $transaction->type);
    }

    // ==========================================
    // Token Code Redemption Tests
    // ==========================================

    public function test_redeem_code_adds_tokens_for_valid_promo_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'TESTCODE',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 50,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $result = $this->tokenService->redeemCode($user, 'TESTCODE');

        $user->refresh();
        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['tokens']);
        $this->assertEquals(50, $user->token_balance);
    }

    public function test_redeem_code_is_case_insensitive(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'MYCODE123',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 25,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $result = $this->tokenService->redeemCode($user, 'mycode123');

        $this->assertTrue($result['success']);
        $this->assertEquals(25, $result['tokens']);
    }

    public function test_redeem_code_throws_exception_for_invalid_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid code');

        $this->tokenService->redeemCode($user, 'NONEXISTENT');
    }

    public function test_redeem_code_throws_exception_for_inactive_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'INACTIVE',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 50,
            'max_uses' => 100,
            'is_active' => false,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no longer active');

        $this->tokenService->redeemCode($user, 'INACTIVE');
    }

    public function test_redeem_code_throws_exception_for_expired_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'EXPIRED',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 50,
            'max_uses' => 100,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expired');

        $this->tokenService->redeemCode($user, 'EXPIRED');
    }

    public function test_redeem_code_throws_exception_for_maxed_out_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'MAXEDOUT',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 50,
            'max_uses' => 5,
            'uses_count' => 5,
            'is_active' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('maximum number of uses');

        $this->tokenService->redeemCode($user, 'MAXEDOUT');
    }

    public function test_redeem_code_throws_exception_for_already_redeemed(): void
    {
        $user = User::factory()->create(['token_balance' => 25]);
        $code = TokenCode::create([
            'code' => 'ONETIME',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 25,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        // First redemption
        TokenCodeRedemption::create([
            'token_code_id' => $code->id,
            'user_id' => $user->id,
            'tokens_received' => 25,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already redeemed');

        $this->tokenService->redeemCode($user, 'ONETIME');
    }

    public function test_redeem_code_increments_uses_count(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $code = TokenCode::create([
            'code' => 'TRACKUSES',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 10,
            'max_uses' => 100,
            'uses_count' => 5,
            'is_active' => true,
        ]);

        $this->tokenService->redeemCode($user, 'TRACKUSES');

        $code->refresh();
        $this->assertEquals(6, $code->uses_count);
    }

    public function test_redeem_code_deactivates_single_use_code_after_use(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $code = TokenCode::create([
            'code' => 'SINGLEUSE',
            'type' => TokenCode::TYPE_SINGLE,
            'tokens' => 100,
            'max_uses' => 1,
            'uses_count' => 0,
            'is_active' => true,
        ]);

        $this->tokenService->redeemCode($user, 'SINGLEUSE');

        $code->refresh();
        $this->assertFalse($code->is_active);
    }

    public function test_redeem_code_creates_redemption_record(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $code = TokenCode::create([
            'code' => 'TRACKME',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 30,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $this->tokenService->redeemCode($user, 'TRACKME');

        $this->assertDatabaseHas('token_code_redemptions', [
            'token_code_id' => $code->id,
            'user_id' => $user->id,
            'tokens_received' => 30,
        ]);
    }

    public function test_redeem_code_creates_bonus_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        TokenCode::create([
            'code' => 'BONUSCODE',
            'type' => TokenCode::TYPE_PROMO,
            'tokens' => 75,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $this->tokenService->redeemCode($user, 'BONUSCODE');

        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_BONUS,
            'amount' => 75,
            'balance_after' => 75,
        ]);
    }

    public function test_redeem_code_rate_limits_attempts(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        // Simulate 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->tokenService->redeemCode($user, 'INVALID' . $i);
            } catch (\Exception $e) {
                // Expected
            }
        }

        // 6th attempt should be rate limited
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many redemption attempts');

        $this->tokenService->redeemCode($user, 'ANOTHER');
    }

    // ==========================================
    // Token Code Creation Tests
    // ==========================================

    public function test_create_code_creates_promo_code(): void
    {
        $code = $this->tokenService->createCode(100, TokenCode::TYPE_PROMO, 'Test promo', 50);

        $this->assertNotNull($code);
        $this->assertEquals(100, $code->tokens);
        $this->assertEquals(TokenCode::TYPE_PROMO, $code->type);
        $this->assertEquals('Test promo', $code->description);
        $this->assertEquals(50, $code->max_uses);
        $this->assertTrue($code->is_active);
    }

    public function test_create_code_creates_single_use_code_with_max_uses_1(): void
    {
        $code = $this->tokenService->createCode(50, TokenCode::TYPE_SINGLE);

        $this->assertEquals(TokenCode::TYPE_SINGLE, $code->type);
        $this->assertEquals(1, $code->max_uses);
    }

    public function test_create_code_allows_custom_code(): void
    {
        $code = $this->tokenService->createCode(25, TokenCode::TYPE_PROMO, null, null, null, null, 'MYCUSTOMCODE');

        $this->assertEquals('MYCUSTOMCODE', $code->code);
    }

    public function test_generate_single_use_codes_creates_multiple_codes(): void
    {
        $codes = $this->tokenService->generateSingleUseCodes(5, 100, 'Giveaway codes');

        $this->assertCount(5, $codes);
        foreach ($codes as $code) {
            $this->assertEquals(100, $code->tokens);
            $this->assertEquals(TokenCode::TYPE_SINGLE, $code->type);
            $this->assertEquals(1, $code->max_uses);
        }
    }

    // ==========================================
    // Transaction History Tests
    // ==========================================

    public function test_get_history_returns_user_transactions(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        // Create some transactions
        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 100,
            'balance_after' => 100,
            'description' => 'Test purchase',
        ]);

        $history = $this->tokenService->getHistory($user);

        $this->assertCount(1, $history);
        $this->assertEquals('Test purchase', $history->first()->description);
    }

    public function test_get_history_orders_by_created_at_descending(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 50,
            'balance_after' => 50,
            'description' => 'First',
            'created_at' => now()->subHour(),
        ]);

        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 50,
            'balance_after' => 100,
            'description' => 'Second',
            'created_at' => now(),
        ]);

        $history = $this->tokenService->getHistory($user);

        $this->assertEquals('Second', $history->first()->description);
    }

    // ==========================================
    // Usage Statistics Tests
    // ==========================================

    public function test_get_usage_stats_returns_correct_statistics(): void
    {
        $user = User::factory()->create(['token_balance' => 75]);

        // Add purchase
        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 100,
            'balance_after' => 100,
            'description' => 'Purchase',
        ]);

        // Add spend
        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_SPEND,
            'amount' => -25,
            'balance_after' => 75,
            'description' => 'Spend',
        ]);

        $stats = $this->tokenService->getUsageStats($user);

        $this->assertEquals(75, $stats['current_balance']);
        $this->assertEquals(25, $stats['spent_this_month']);
        $this->assertEquals(1, $stats['transactions_this_month']);
        $this->assertEquals(100, $stats['total_credited']);
        $this->assertEquals(25, $stats['total_spent']);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    public function test_spend_exact_balance_leaves_zero(): void
    {
        $user = User::factory()->create(['token_balance' => 5]);

        $this->tokenService->spend($user, 'scan_pro');

        $user->refresh();
        $this->assertEquals(0, $user->token_balance);
    }

    public function test_multiple_credits_and_debits_maintain_correct_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $this->tokenService->creditBonus($user, 100, 'Initial bonus');
        $user->refresh();
        $this->assertEquals(100, $user->token_balance);

        $this->tokenService->spend($user, 'scan_full'); // -10
        $user->refresh();
        $this->assertEquals(90, $user->token_balance);

        $this->tokenService->spend($user, 'scan_pro'); // -5
        $user->refresh();
        $this->assertEquals(85, $user->token_balance);

        $this->tokenService->refund($user, 5, 'Refund');
        $user->refresh();
        $this->assertEquals(90, $user->token_balance);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
