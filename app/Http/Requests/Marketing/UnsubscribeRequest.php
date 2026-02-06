<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates marketing email unsubscribe requests.
 */
class UnsubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function getEmail(): string
    {
        return $this->input('email');
    }

    public function getReason(): ?string
    {
        return $this->input('reason');
    }
}
