<?php

namespace App\Services;

use App\Models\TokenPackage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles business logic for Stripe webhook events.
 */
class StripeWebhookService
{
    public function __construct(
        protected TokenService $tokenService,
    ) {}

    /**
     * Process a completed checkout session for token purchases.
     *
     * @return bool Whether the purchase was processed successfully
     */
    public function processTokenPurchase(array $session): bool
    {
        $metadata = $session['metadata'] ?? [];

        // Only handle token purchases
        if (($metadata['type'] ?? null) !== 'token_purchase') {
            return true;
        }

        $userId = $metadata['user_id'] ?? null;
        $packageId = $metadata['package_id'] ?? null;

        if (! $userId || ! $packageId) {
            Log::warning('Token purchase webhook missing metadata', [
                'session_id' => $session['id'],
                'metadata' => $metadata,
            ]);

            return true;
        }

        $user = User::find($userId);
        $package = TokenPackage::find($packageId);

        if (! $user || ! $package) {
            Log::warning('Token purchase webhook: user or package not found', [
                'session_id' => $session['id'],
                'user_id' => $userId,
                'package_id' => $packageId,
            ]);

            return true;
        }

        // Check if payment was successful
        if ($session['payment_status'] !== 'paid') {
            Log::info('Token purchase webhook: payment not completed', [
                'session_id' => $session['id'],
                'payment_status' => $session['payment_status'],
            ]);

            return true;
        }

        // Credit tokens to user
        try {
            $this->tokenService->creditPurchase($user, $package, $session['id']);

            Log::info('Token purchase completed via webhook', [
                'session_id' => $session['id'],
                'user_id' => $user->id,
                'package_id' => $package->id,
                'tokens' => $package->tokens,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Token purchase webhook error', [
                'session_id' => $session['id'],
                'user_id' => $userId,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
