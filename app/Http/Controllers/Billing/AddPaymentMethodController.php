<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Adds a new payment method to the user's account.
 */
class AddPaymentMethodController extends Controller
{
    public function __invoke(PaymentMethodRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $user->addPaymentMethod($request->getPaymentMethodId());

            if (! $user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($request->getPaymentMethodId());
            }

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Payment method added successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
