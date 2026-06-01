<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentMethodRequest;
use App\Support\ErrorSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\InvalidPaymentMethod;

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
        } catch (InvalidPaymentMethod $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Failed to delete payment method', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => ErrorSanitizer::sanitize($e)]);
        }
    }
}
