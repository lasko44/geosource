<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use App\Support\ErrorSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\InvalidPaymentMethod;

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
        } catch (InvalidPaymentMethod $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => ErrorSanitizer::sanitize($e)]);
        }
    }
}
