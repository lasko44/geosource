<?php

namespace App\Http\Requests\Experiments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a guest scan URL submission from the homepage experiment.
 */
class GuestScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
        ];
    }
}
