<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Removes a payment method from the user's account.
 */
class DeletePaymentMethodController extends Controller
{
    public function __invoke(PaymentMethodRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $paymentMethod = $user->findPaymentMethod($request->getPaymentMethodId());

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
