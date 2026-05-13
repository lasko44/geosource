<?php

namespace App\Http\Controllers\Api\V1\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the authenticated user's token balance and usage stats.
 */
class TokenBalanceApiController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): JsonResponse
    {
        $user = $request->user();

        return response()->json($tokenService->getUsageStats($user));
    }
}
