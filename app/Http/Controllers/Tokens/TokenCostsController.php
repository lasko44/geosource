<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Returns token cost configuration via API.
 */
class TokenCostsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'costs' => config('tokens.costs'),
            'labels' => config('tokens.labels'),
        ]);
    }
}
