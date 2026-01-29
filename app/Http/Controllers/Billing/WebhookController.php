<?php

namespace App\Http\Controllers\Billing;

use App\Models\Team;
use App\Models\TokenPackage;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierController
{
    /**
     * Get the billable entity instance by Stripe ID.
     *
     * @param  string|null  $stripeId
     * @return \Laravel\Cashier\Billable|null
     */
    protected function getUserByStripeId($stripeId)
    {
        if ($stripeId === null) {
            return null;
        }

        // First try to find a User with this Stripe ID
        $user = User::where('stripe_id', $stripeId)->first();

        if ($user) {
            return $user;
        }

        // Then try to find a Team with this Stripe ID
        return Team::where('stripe_id', $stripeId)->first();
    }

    /**
     * Handle checkout session completed event (for token purchases).
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];
        $metadata = $session['metadata'] ?? [];

        // Only handle token purchases
        if (($metadata['type'] ?? null) !== 'token_purchase') {
            return $this->successMethod();
        }

        $userId = $metadata['user_id'] ?? null;
        $packageId = $metadata['package_id'] ?? null;

        if (!$userId || !$packageId) {
            Log::warning('Token purchase webhook missing metadata', [
                'session_id' => $session['id'],
                'metadata' => $metadata,
            ]);
            return $this->successMethod();
        }

        $user = User::find($userId);
        $package = TokenPackage::find($packageId);

        if (!$user || !$package) {
            Log::warning('Token purchase webhook: user or package not found', [
                'session_id' => $session['id'],
                'user_id' => $userId,
                'package_id' => $packageId,
            ]);
            return $this->successMethod();
        }

        // Check if payment was successful
        if ($session['payment_status'] !== 'paid') {
            Log::info('Token purchase webhook: payment not completed', [
                'session_id' => $session['id'],
                'payment_status' => $session['payment_status'],
            ]);
            return $this->successMethod();
        }

        // Credit tokens to user
        $tokenService = app(TokenService::class);

        try {
            $tokenService->creditPurchase($user, $package, $session['id']);

            Log::info('Token purchase completed via webhook', [
                'session_id' => $session['id'],
                'user_id' => $user->id,
                'package_id' => $package->id,
                'tokens' => $package->tokens,
            ]);
        } catch (\Exception $e) {
            Log::error('Token purchase webhook error', [
                'session_id' => $session['id'],
                'user_id' => $userId,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->successMethod();
    }
}
