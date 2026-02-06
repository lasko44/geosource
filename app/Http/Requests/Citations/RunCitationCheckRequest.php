<?php

namespace App\Http\Requests\Citations;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to run a single citation check.
 */
class RunCitationCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        $query = $this->route('query');

        if (! $query instanceof CitationQuery) {
            return false;
        }

        $user = $this->user();

        if ($query->user_id === $user->id) {
            return true;
        }

        if ($query->team_id && $user->allTeams()->contains('id', $query->team_id)) {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|string|in:perplexity,openai,claude,gemini,deepseek,google,youtube,facebook',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();
                $platform = $this->platform;
                $service = app(CitationService::class);

                // Rate limit check
                $recentCheck = CitationCheck::where('user_id', $user->id)
                    ->where('platform', $platform)
                    ->where('created_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($recentCheck) {
                    $validator->errors()->add('rate_limit', 'Please wait a few seconds between checks on the same platform.');

                    return;
                }

                // Platform access check
                $availablePlatforms = $service->getAvailablePlatforms($user);
                if (! in_array($platform, $availablePlatforms)) {
                    $validator->errors()->add('platform', 'This platform is not available. You need tokens to run citation checks.');

                    return;
                }

                // Token balance check
                $tokenCost = $service->getTokenCost($platform);
                if (! $user->is_admin && $tokenCost > 0 && ($user->token_balance ?? 0) < $tokenCost) {
                    $validator->errors()->add('tokens', "You need {$tokenCost} tokens for this check. You have {$user->token_balance} tokens.");
                }
            },
        ];
    }

    public function getTokenCost(): int
    {
        return app(CitationService::class)->getTokenCost($this->platform);
    }
}
