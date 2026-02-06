<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the token purchase page with available packages.
 */
class TokenPurchaseController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): Response
    {
        $user = $request->user();

        return Inertia::render('Tokens/Purchase', [
            'packages' => $tokenService->getPackages()->map(fn ($package) => [
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
}
