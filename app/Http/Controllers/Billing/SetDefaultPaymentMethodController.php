<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sets a payment method as the default for billing.
 */
class SetDefaultPaymentMethodController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $request->user()->updateDefaultPaymentMethod($request->input('payment_method'));

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Default payment method updated!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
