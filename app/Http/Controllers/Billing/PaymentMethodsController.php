<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the user's saved payment methods.
 */
class PaymentMethodsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('billing/PaymentMethods', [
            'paymentMethods' => $user->paymentMethods()->map(fn ($method) => [
                'id' => $method->id,
                'brand' => $method->card->brand,
                'last_four' => $method->card->last4,
                'exp_month' => $method->card->exp_month,
                'exp_year' => $method->card->exp_year,
                'is_default' => $method->id === $user->defaultPaymentMethod()?->id,
            ]),
            'intent' => $user->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
        ]);
    }
}
