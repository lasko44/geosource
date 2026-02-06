<?php

namespace App\Http\Requests\GA4;

use App\Models\GA4Connection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests to fetch GA4 analytics data.
 */
class GA4DataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->route('connection');

        return $connection instanceof GA4Connection
            && $this->user()->can('view', $connection);
    }

    public function rules(): array
    {
        return [
            'days' => 'nullable|integer|min:7|max:90',
        ];
    }

    public function getDays(): int
    {
        return (int) $this->input('days', 30);
    }
}
