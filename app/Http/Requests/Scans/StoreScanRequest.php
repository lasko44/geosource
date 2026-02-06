<?php

namespace App\Http\Requests\Scans;

use App\Models\Team;
use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to create a new website scan.
 */
class StoreScanRequest extends FormRequest
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
            'team_id' => 'nullable|integer',
            'tier' => 'nullable|in:basic,pro,full',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Skip additional validation if basic rules failed
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $scanService = app(ScanService::class);
                $user = $this->user();

                // Validate team context
                $requestTeamId = $this->input('team_id');
                $storedTeamId = session('current_team_id');
                $sessionIsPersonal = ! $storedTeamId || $storedTeamId === 'personal';
                $requestIsPersonal = $requestTeamId === null;

                if ($sessionIsPersonal !== $requestIsPersonal) {
                    $validator->errors()->add('team_id', 'Team context mismatch. Please refresh the page and try again.');

                    return;
                }

                if (! $requestIsPersonal) {
                    if ((int) $requestTeamId !== (int) $storedTeamId) {
                        $validator->errors()->add('team_id', 'Team context mismatch. Please refresh the page and try again.');

                        return;
                    }

                    $team = $user->allTeams()->firstWhere('id', $requestTeamId);
                    if (! $team) {
                        $validator->errors()->add('team_id', 'You do not have access to this team.');

                        return;
                    }
                    $this->validatedTeam = $team;
                }

                // Check cooldown
                $tier = $this->input('tier', 'basic');
                $cooldown = $scanService->checkCooldown($this->url, $user->id, $tier);

                if ($cooldown) {
                    $minutes = $cooldown['minutes_remaining'];
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

    public function getTier(): string
    {
        return $this->input('tier', 'basic');
    }
}
