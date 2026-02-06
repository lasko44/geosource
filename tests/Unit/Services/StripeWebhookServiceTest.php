<?php

namespace Tests\Unit\Services;

use App\Models\TokenPackage;
use App\Models\User;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StripeWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StripeWebhookService::class);
    }

    // ==========================================
    // Process Token Purchase Tests
    // ==========================================

    public function test_process_token_purchase_returns_true_for_non_token_type(): void
    {
        $session = [
            'id' => 'cs_test_123',
            'metadata' => [
                'type' => 'subscription',
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_empty_metadata(): void
    {
        $session = [
            'id' => 'cs_test_123',
            'metadata' => [],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_missing_user_id(): void
    {
        $session = [
            'id' => 'cs_test_123',
            'metadata' => [
                'type' => 'token_purchase',
                'package_id' => 1,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_missing_package_id(): void
    {
        $session = [
            'id' => 'cs_test_123',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => 1,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_invalid_user(): void
    {
        $package = TokenPackage::factory()->create();

        $session = [
            'id' => 'cs_test_123',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => 99999,
                'package_id' => $package->id,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_invalid_package(): void
    {
        $user = User::factory()->create();

        $session = [
            'id' => 'cs_test_123',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => $user->id,
                'package_id' => 99999,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
    }

    public function test_process_token_purchase_returns_true_for_unpaid_payment(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $session = [
            'id' => 'cs_test_123',
            'payment_status' => 'unpaid',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => $user->id,
                'package_id' => $package->id,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
        $this->assertEquals(0, $user->fresh()->token_balance);
    }

    public function test_process_token_purchase_credits_tokens_on_success(): void
    {
        $user = User::factory()->create(['token_balance' => 50]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $session = [
            'id' => 'cs_test_123',
            'payment_status' => 'paid',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => $user->id,
                'package_id' => $package->id,
            ],
        ];

        $result = $this->service->processTokenPurchase($session);

        $this->assertTrue($result);
        $this->assertEquals(150, $user->fresh()->token_balance);
    }

    public function test_process_token_purchase_creates_transaction(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $package = TokenPackage::factory()->create(['tokens' => 100]);

        $session = [
            'id' => 'cs_test_123',
            'payment_status' => 'paid',
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => $user->id,
                'package_id' => $package->id,
            ],
        ];

        $this->service->processTokenPurchase($session);

        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'purchase',
        ]);
    }
}
