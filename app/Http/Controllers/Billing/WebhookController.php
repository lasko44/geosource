<?php

namespace App\Http\Controllers\Billing;

use App\Models\Team;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

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
}
