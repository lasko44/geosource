<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests that require a Stripe payment method ID.
 */
class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => 'required|string',
        ];
    }

    public function getPaymentMethodId(): string
    {
        return $this->input('payment_method');
    }
}
