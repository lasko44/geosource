<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Redeems a token code for the user.
 */
class RedeemTokenCodeController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $user = $request->user();

        try {
            $result = $tokenService->redeemCode($user, $request->code);

            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }
    }
}
