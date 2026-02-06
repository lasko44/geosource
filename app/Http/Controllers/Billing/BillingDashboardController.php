<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the user's billing dashboard and subscription status.
 */
class BillingDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        $planDetails = null;
        if ($subscription) {
            $plans = config('billing.plans.user');
            foreach ($plans as $key => $plan) {
                if ($plan['price_id'] === $subscription->stripe_price) {
                    $planDetails = [
                        'name' => $plan['name'],
                        'price' => $plan['price'],
                        'currency' => $plan['currency'] ?? 'USD',
                        'interval' => $plan['interval'] ?? 'month',
                    ];
                    break;
                }
            }
        }

        return Inertia::render('billing/Index', [
            'subscription' => $subscription ? [
                'name' => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'plan_name' => $planDetails['name'] ?? null,
                'plan_price' => $planDetails['price'] ?? null,
                'plan_currency' => $planDetails['currency'] ?? 'USD',
                'plan_interval' => $planDetails['interval'] ?? 'month',
                'ends_at' => $subscription->ends_at?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'canceled' => $subscription->canceled(),
                'active' => $subscription->active(),
            ] : null,
            'defaultPaymentMethod' => $user->defaultPaymentMethod() ? [
                'brand' => $user->pm_type,
                'last_four' => $user->pm_last_four,
            ] : null,
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trial_ends_at?->toISOString(),
        ]);
    }
}
