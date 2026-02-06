<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the user's current token balance via API.
 */
class TokenBalanceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'balance' => $request->user()->token_balance,
        ]);
    }
}
