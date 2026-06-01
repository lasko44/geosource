<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use App\Support\ErrorSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\InvalidPaymentMethod;

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
        } catch (InvalidPaymentMethod $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Failed to add payment method', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => ErrorSanitizer::sanitize($e)]);
        }
    }
}
