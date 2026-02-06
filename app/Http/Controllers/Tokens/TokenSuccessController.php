<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the token purchase success page.
 */
class TokenSuccessController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('tokens.index');
        }

        session(['token_purchase_session_id' => $sessionId]);

        return Inertia::render('Tokens/Success', [
            'balance' => $request->user()->fresh()->token_balance,
        ]);
    }
}
