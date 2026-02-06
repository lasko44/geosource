<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Adds a new payment method to the user's account.
 */
class AddPaymentMethodController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $user->addPaymentMethod($request->input('payment_method'));

            if (! $user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($request->input('payment_method'));
            }

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Payment method added successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
