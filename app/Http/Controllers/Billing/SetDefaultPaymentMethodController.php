<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Sets a payment method as the default for billing.
 */
class SetDefaultPaymentMethodController extends Controller
{
    public function __invoke(PaymentMethodRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateDefaultPaymentMethod($request->getPaymentMethodId());

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Default payment method updated!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
