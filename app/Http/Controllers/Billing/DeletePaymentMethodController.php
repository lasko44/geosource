<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Removes a payment method from the user's account.
 */
class DeletePaymentMethodController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $paymentMethod = $user->findPaymentMethod($request->input('payment_method'));

            if ($paymentMethod) {
                $paymentMethod->delete();
            }

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Payment method removed!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
