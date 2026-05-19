<?php

namespace App\Http\Requests\Experiments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a guest citation check submission from the homepage experiment.
 */
class GuestCitationCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255'],
            'query' => ['required', 'string', 'max:500'],
        ];
    }
}
