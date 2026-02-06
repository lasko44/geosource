<?php

namespace App\Http\Requests\Citations;

use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to run citation checks on multiple platforms.
 */
class RunBulkCitationCheckRequest extends FormRequest
{
    protected ?array $validatedPlatforms = null;

    protected int $totalCost = 0;

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
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'string|in:perplexity,openai,claude,gemini,deepseek,google,youtube,facebook',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();
                $service = app(CitationService::class);

                // Filter to available platforms
                $availablePlatforms = $service->getAvailablePlatforms($user);
                $this->validatedPlatforms = array_intersect($this->platforms, $availablePlatforms);

                if (empty($this->validatedPlatforms)) {
                    $validator->errors()->add('platforms', 'No valid platforms specified. You need tokens to run citation checks.');

                    return;
                }

                // Calculate total cost
                $this->totalCost = 0;
                foreach ($this->validatedPlatforms as $platform) {
                    $this->totalCost += $service->getTokenCost($platform);
                }

                // Check token balance
                if (! $user->is_admin && $this->totalCost > 0 && ($user->token_balance ?? 0) < $this->totalCost) {
                    $validator->errors()->add('tokens', "You need {$this->totalCost} tokens for all checks. You have {$user->token_balance} tokens.");
                }
            },
        ];
    }

    public function getValidatedPlatforms(): array
    {
        return $this->validatedPlatforms ?? [];
    }

    public function getTotalCost(): int
    {
        return $this->totalCost;
    }
}
