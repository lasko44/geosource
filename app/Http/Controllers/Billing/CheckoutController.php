<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the subscription checkout page.
 */
class CheckoutController extends Controller
{
    public function __invoke(Request $request, string $plan): Response
    {
        $planConfig = config("billing.plans.user.{$plan}");

        if (! $planConfig) {
            abort(404);
        }

        return Inertia::render('billing/Subscribe', [
            'plan' => array_merge($planConfig, ['key' => $plan]),
            'intent' => $request->user()->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
        ]);
    }
}
