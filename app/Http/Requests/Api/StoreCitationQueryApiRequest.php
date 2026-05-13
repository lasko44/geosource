<?php

namespace App\Http\Requests\Api;

use App\Models\Team;
use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates API requests to create a new citation query.
 */
class StoreCitationQueryApiRequest extends FormRequest
{
    protected ?Team $validatedTeam = null;

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
            'frequency' => 'nullable|string|in:manual,daily,weekly',
            'scheduled_platforms' => 'nullable|array',
            'scheduled_platforms.*' => 'string|in:perplexity,openai,claude,gemini,deepseek,google,youtube,facebook',
            'monthly_token_budget' => 'nullable|integer|min:0',
            'team_id' => 'nullable|integer',
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
                $team = $this->getValidatedTeam();

                if (! $service->canCreateQuery($user, $team)) {
                    $validator->errors()->add('limit', 'You have reached your citation query limit.');
                }

                $frequency = $this->input('frequency', 'manual');
                $availableFrequencies = $service->getAvailableFrequencies($user);
                if (! in_array($frequency, $availableFrequencies)) {
                    $validator->errors()->add('frequency', 'This frequency is not available on your current plan.');
                }
            },
        ];
    }

    public function getValidatedTeam(): ?Team
    {
        if ($this->validatedTeam !== null) {
            return $this->validatedTeam;
        }

        $teamId = $this->input('team_id');

        if (! $teamId) {
            return null;
        }

        $this->validatedTeam = $this->user()->allTeams()->firstWhere('id', (int) $teamId);

        return $this->validatedTeam;
    }
}
