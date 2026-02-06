<?php

namespace App\Http\Requests\Citations;

use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to update a citation query.
 */
class UpdateCitationQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $query = $this->route('query');

        return $query instanceof CitationQuery && $query->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'query' => 'sometimes|string|max:500',
            'domain' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i',
            ],
            'brand' => 'nullable|string|max:255',
            'frequency' => 'sometimes|string|in:manual,daily,weekly',
            'is_active' => 'sometimes|boolean',
            'scheduled_platforms' => 'nullable|array',
            'scheduled_platforms.*' => 'string|in:perplexity,openai,claude,gemini,deepseek,google,youtube,facebook',
            'monthly_token_budget' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'domain.regex' => 'Please enter a valid domain (e.g., example.com).',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->has('frequency')) {
                    $service = app(CitationService::class);
                    $availableFrequencies = $service->getAvailableFrequencies($this->user());

                    if (! in_array($this->frequency, $availableFrequencies)) {
                        $validator->errors()->add('frequency', 'This frequency is not available on your current plan.');
                    }
                }
            },
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if ($this->has('scheduled_platforms')) {
            $platforms = $this->input('scheduled_platforms', []);
            $data['scheduled_platforms'] = ! empty($platforms) ? $platforms : null;
        }

        return $data;
    }
}
