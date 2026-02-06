<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates subscription creation requests.
 */
class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => 'required|string',
            'payment_method' => 'required|string',
        ];
    }

    public function getPlan(): string
    {
        return $this->input('plan');
    }

    public function getPaymentMethodId(): string
    {
        return $this->input('payment_method');
    }

    public function getPlanConfig(): ?array
    {
        return config("billing.plans.user.{$this->getPlan()}");
    }
}
