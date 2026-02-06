<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tokens\TokenCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Creates a Stripe checkout session for token purchase.
 */
class TokenCheckoutController extends Controller
{
    public function __invoke(TokenCheckoutRequest $request): JsonResponse
    {
        $package = $request->getPackage();

        if (! $package->is_active) {
            return response()->json(['error' => 'This package is no longer available.'], 400);
        }

        if (! $package->stripe_price_id) {
            return response()->json(['error' => 'This package is not configured for purchase.'], 400);
        }

        $user = $request->user();

        Stripe::setApiKey(config('cashier.secret'));

        try {
            $checkoutSession = Session::create([
                'customer' => $user->stripe_id ?? null,
                'customer_email' => $user->stripe_id ? null : $user->email,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => [[
                    'price' => $package->stripe_price_id,
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'type' => 'token_purchase',
                ],
                'success_url' => route('tokens.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('tokens.index'),
            ]);

            return response()->json(['checkout_url' => $checkoutSession->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to create checkout session. Please try again.'], 500);
        }
    }
}
