<?php

namespace App\Http\Controllers;

use App\Models\TokenPackage;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class TokenController extends Controller
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    /**
     * Show the token purchase page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Tokens/Purchase', [
            'packages' => $this->tokenService->getPackages()->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'tokens' => $package->tokens,
                'price' => $package->price,
                'formatted_price' => $package->formatted_price,
                'price_per_token' => round($package->price_per_token, 3),
                'savings_percent' => $package->savings_percent,
                'is_popular' => $package->is_popular,
            ]),
            'balance' => $user->token_balance,
            'costs' => config('tokens.costs'),
            'labels' => config('tokens.labels'),
        ]);
    }

    /**
     * Create a Stripe Checkout session for token purchase.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:token_packages,id',
        ]);

        $package = TokenPackage::findOrFail($request->package_id);

        if (!$package->is_active) {
            return response()->json(['error' => 'This package is no longer available.'], 400);
        }

        if (!$package->stripe_price_id) {
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
                'success_url' => route('tokens.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('tokens.index'),
            ]);

            return response()->json(['checkout_url' => $checkoutSession->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to create checkout session. Please try again.'], 500);
        }
    }

    /**
     * Handle successful token purchase redirect.
     */
    public function success(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('tokens.index');
        }

        // Store session ID for the thank you page display
        // Tokens are credited via webhook, not here
        session(['token_purchase_session_id' => $sessionId]);

        return Inertia::render('Tokens/Success', [
            'balance' => $request->user()->fresh()->token_balance,
        ]);
    }

    /**
     * Show token transaction history.
     */
    public function history(Request $request): Response
    {
        $user = $request->user();
        $transactions = $this->tokenService->getHistory($user, 100);

        return Inertia::render('Tokens/History', [
            'transactions' => $transactions->map(fn ($t) => [
                'uuid' => $t->uuid,
                'type' => $t->type,
                'amount' => $t->amount,
                'formatted_amount' => $t->formatted_amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at->toISOString(),
            ]),
            'balance' => $user->token_balance,
            'stats' => $this->tokenService->getUsageStats($user),
        ]);
    }

    /**
     * Get current token balance (API endpoint).
     */
    public function balance(Request $request): JsonResponse
    {
        return response()->json([
            'balance' => $request->user()->token_balance,
        ]);
    }

    /**
     * Get token costs configuration (API endpoint).
     */
    public function costs(): JsonResponse
    {
        return response()->json([
            'costs' => config('tokens.costs'),
            'labels' => config('tokens.labels'),
        ]);
    }
}
