<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates team invitation creation requests.
 */
class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ];
    }

    public function getEmail(): string
    {
        return strtolower($this->input('email'));
    }

    public function getRole(): string
    {
        return $this->input('role');
    }
}
