<?php

namespace App\Http\Requests\Citations;

use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to create a new citation query.
 */
class StoreCitationQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CitationService::class)->canAccessCitations($this->user());
    }

    public function rules(): array
    {
        return [
            'query' => 'required|string|max:500',
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i',
            ],
            'brand' => 'nullable|string|max:255',
            'frequency' => 'required|string|in:manual,daily,weekly',
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
                $service = app(CitationService::class);
                $user = $this->user();
                $team = $this->getTeam();

                if (! $service->canCreateQuery($user, $team)) {
                    $validator->errors()->add('limit', 'You have reached your citation query limit. Please upgrade your plan.');
                }

                $availableFrequencies = $service->getAvailableFrequencies($user);
                if (! in_array($this->frequency, $availableFrequencies)) {
                    $validator->errors()->add('frequency', 'This frequency is not available on your current plan.');
                }
            },
        ];
    }

    public function getTeam(): ?\App\Models\Team
    {
        $currentTeamId = session('current_team_id');

        if ($currentTeamId && $currentTeamId !== 'personal') {
            return $this->user()->allTeams()->firstWhere('id', $currentTeamId);
        }

        return null;
    }
}
