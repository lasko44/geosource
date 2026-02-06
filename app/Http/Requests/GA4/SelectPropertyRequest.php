<?php

namespace App\Http\Requests\GA4;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests to select a GA4 property for connection.
 */
class SelectPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|string',
            'property_name' => 'required|string',
            'account_id' => 'required|string',
        ];
    }

    public function getPropertyData(): array
    {
        return [
            'property_id' => $this->input('property_id'),
            'property_name' => $this->input('property_name'),
            'account_id' => $this->input('account_id'),
        ];
    }
}
