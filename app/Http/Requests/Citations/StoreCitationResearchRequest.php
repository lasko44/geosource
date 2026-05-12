<?php

namespace App\Http\Requests\Citations;

use App\Models\CitationResearch;
use App\Models\Team;
use App\Services\Citation\CitationResearchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Validator;

/**
 * Validates requests to create a new citation research.
 */
class StoreCitationResearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CitationResearchService::class)->canAccessResearch($this->user());
    }

    public function rules(): array
    {
        return [
            'topic' => 'required|string|min:3|max:500',
            'tier' => 'required|string|in:research,research_audit',
        ];
    }

    public function messages(): array
    {
        return [
            'topic.min' => 'Please provide a more detailed research topic (at least 3 characters).',
            'topic.max' => 'Research topic is too long. Please keep it under 500 characters.',
            'tier.in' => 'Invalid research tier selected.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $service = app(CitationResearchService::class);
                $user = $this->user();
                $tier = $this->getTier();

                // Check token balance
                if (! $service->canAfford($user, $tier)) {
                    $cost = $service->getTokenCost($tier);
                    $validator->errors()->add(
                        'tokens',
                        "Insufficient tokens. You need {$cost} tokens for this research."
                    );
                }

                // Rate limit: max 5 research requests per hour
                $key = 'citation_research:'.$user->id;
                if (RateLimiter::tooManyAttempts($key, 5)) {
                    $seconds = RateLimiter::availableIn($key);
                    $minutes = ceil($seconds / 60);
                    $validator->errors()->add(
                        'rate_limit',
                        "Too many research requests. Please wait {$minutes} minute(s) before trying again."
                    );
                }
            },
        ];
    }

    /**
     * Handle successful validation - increment rate limiter.
     */
    protected function passedValidation(): void
    {
        $key = 'citation_research:'.$this->user()->id;
        RateLimiter::hit($key, 3600); // 1 hour decay
    }

    /**
     * Get the research tier.
     */
    public function getTier(): string
    {
        return $this->input('tier', CitationResearch::TIER_RESEARCH);
    }

    /**
     * Get the token cost for this request.
     */
    public function getTokenCost(): int
    {
        return app(CitationResearchService::class)->getTokenCost($this->getTier());
    }

    /**
     * Get the team from session context.
     */
    public function getTeam(): ?Team
    {
        $currentTeamId = session('current_team_id');

        if ($currentTeamId && $currentTeamId !== 'personal') {
            return $this->user()->allTeams()->firstWhere('id', $currentTeamId);
        }

        return null;
    }
}
