<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the user's token transaction history.
 */
class TokenHistoryController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): Response
    {
        $user = $request->user();
        $transactions = $tokenService->getHistory($user, 100);

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
            'stats' => $tokenService->getUsageStats($user),
        ]);
    }
}
