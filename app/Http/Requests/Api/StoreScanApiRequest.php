<?php

namespace App\Http\Requests\Api;

use App\Models\Team;
use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

/**
 * Validates API requests to create a new website scan.
 */
class StoreScanApiRequest extends FormRequest
{
    protected ?Team $validatedTeam = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'tier' => 'nullable|in:basic,pro,full',
            'team_id' => 'nullable|integer',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $scanService = app(ScanService::class);
                $user = $this->user();

                // Validate team context from explicit parameter
                $teamId = $this->input('team_id');
                if ($teamId) {
                    $team = $user->allTeams()->firstWhere('id', (int) $teamId);
                    if (! $team) {
                        $validator->errors()->add('team_id', 'You do not have access to this team.');

                        return;
                    }
                    $this->validatedTeam = $team;
                }

                // Check cooldown
                $tier = $this->input('tier', 'basic');
                $cooldown = $scanService->checkCooldown($this->input('url'), $user->id, $tier);

                if ($cooldown) {
                    $minutes = Arr::get($cooldown, 'minutes_remaining');
                    $word = $minutes === 1 ? 'minute' : 'minutes';
                    $validator->errors()->add('cooldown', "This URL was scanned recently. Please wait {$minutes} {$word} before scanning again.");
                }
            },
        ];
    }

    public function getValidatedTeam(): ?Team
    {
        return $this->validatedTeam;
    }
}
