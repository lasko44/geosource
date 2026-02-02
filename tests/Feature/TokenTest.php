<?php

namespace Tests\Feature;

use App\Models\TokenCode;
use App\Models\TokenCodeRedemption;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_valid_promo_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        TokenCode::create([
            'code' => 'TESTPROMO50',
            'type' => 'promo',
            'tokens' => 50,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/tokens/redeem', [
            'code' => 'TESTPROMO50',
        ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals(50, $user->token_balance);
    }

    public function test_user_cannot_redeem_same_code_twice(): void
    {
        $user = User::factory()->create(['token_balance' => 25]);

        $code = TokenCode::create([
            'code' => 'ONCEPERUSER',
            'type' => 'promo',
            'tokens' => 25,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        TokenCodeRedemption::create([
            'user_id' => $user->id,
            'token_code_id' => $code->id,
            'tokens_received' => 25,
        ]);

        $response = $this->actingAs($user)->post('/tokens/redeem', [
            'code' => 'ONCEPERUSER',
        ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertEquals(25, $user->token_balance);
    }

    public function test_user_cannot_redeem_expired_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        TokenCode::create([
            'code' => 'EXPIREDCODE',
            'type' => 'promo',
            'tokens' => 50,
            'max_uses' => 100,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->post('/tokens/redeem', [
            'code' => 'EXPIREDCODE',
        ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertEquals(0, $user->token_balance);
    }

    public function test_user_cannot_redeem_invalid_code(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($user)->post('/tokens/redeem', [
            'code' => 'INVALIDCODE123',
        ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertEquals(0, $user->token_balance);
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

        $response = $this->actingAs($user)->get('/tokens/history');

        $response->assertOk();
    }

    public function test_user_can_view_token_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tokens');

        $response->assertOk();
    }
}
