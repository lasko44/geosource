<?php

namespace App\Http\Controllers\Billing;

use App\Models\Team;
use App\Models\User;
use App\Services\StripeWebhookService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles incoming Stripe webhook events.
 *
 * Note: This controller extends Cashier's WebhookController which requires protected methods
 * for event handling (handle{EventName} convention) and billable resolution (getUserByStripeId).
 * This is a framework requirement, not a violation of ADR-011.
 */
class WebhookController extends CashierController
{
    /**
     * Get the billable entity instance by Stripe ID.
     *
     * Override Cashier's method to support both User and Team billables.
     */
    protected function getUserByStripeId($stripeId): User|Team|null
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
     *
     * Cashier convention: protected handle{EventName} methods are auto-called for webhook events.
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        app(StripeWebhookService::class)->processTokenPurchase($session);

        return $this->successMethod();
    }
}
