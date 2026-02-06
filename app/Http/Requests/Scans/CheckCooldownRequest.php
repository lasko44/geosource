<?php

namespace App\Http\Requests\Scans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates cooldown check requests.
 */
class CheckCooldownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'tier' => 'nullable|in:basic,pro,full',
        ];
    }

    public function getUrl(): string
    {
        return $this->input('url');
    }

    public function getTier(): string
    {
        return $this->input('tier', 'basic');
    }
}
