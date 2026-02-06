<?php

namespace App\Http\Requests\Tokens;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates token code redemption requests.
 */
class RedeemTokenCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
        ];
    }

    public function getCode(): string
    {
        return $this->input('code');
    }
}
