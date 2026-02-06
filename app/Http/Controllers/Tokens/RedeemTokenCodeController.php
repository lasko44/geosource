<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tokens\RedeemTokenCodeRequest;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;

/**
 * Redeems a token code for the user.
 */
class RedeemTokenCodeController extends Controller
{
    public function __invoke(RedeemTokenCodeRequest $request, TokenService $tokenService): RedirectResponse
    {
        try {
            $result = $tokenService->redeemCode($request->user(), $request->getCode());

            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }
    }
}
