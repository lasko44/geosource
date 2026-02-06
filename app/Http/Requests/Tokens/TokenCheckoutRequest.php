<?php

namespace App\Http\Requests\Tokens;

use App\Models\TokenPackage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates token checkout requests.
 */
class TokenCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => 'required|exists:token_packages,id',
        ];
    }

    public function getPackage(): TokenPackage
    {
        return TokenPackage::findOrFail($this->input('package_id'));
    }
}
