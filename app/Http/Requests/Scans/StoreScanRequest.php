<?php

namespace App\Http\Requests\Scans;

use App\Models\Scan;
use App\Models\Team;
use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
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
            'is_competitor' => 'nullable|boolean',
            'auto_find_competitors' => 'nullable|boolean',
            'competitor_scan_tier' => 'nullable|in:basic,pro,full',
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

                // Check competitor limit
                if ($this->boolean('is_competitor')) {
                    $teamId = $requestIsPersonal ? null : (int) $requestTeamId;
                    $userId = $user->id;

                    if (! Scan::canAddCompetitor($teamId, $userId)) {
                        $remaining = Scan::remainingCompetitorSlots($teamId, $userId);
                        $max = Scan::MAX_COMPETITORS_PER_TEAM;
                        $validator->errors()->add(
                            'is_competitor',
                            "You have reached the maximum of {$max} competitor scans. You have {$remaining} slots remaining."
                        );

                        return;
                    }
                }

                // Check cooldown
                $tier = $this->input('tier', 'basic');
                $cooldown = $scanService->checkCooldown($this->url, $user->id, $tier);

                if ($cooldown) {
                    $minutes = Arr::get($cooldown, 'minutes_remaining');
                    $word = $minutes === 1 ? 'minute' : 'minutes';
                    $validator->errors()->add('cooldown', "This URL was scanned recently. Please wait {$minutes} {$word} before scanning again.");
                }

                // Validate auto_find_competitors (only on full tier)
                if ($this->boolean('auto_find_competitors')) {
                    if ($tier !== 'full') {
                        $validator->errors()->add('auto_find_competitors', 'Auto-find competitors is only available on Full tier scans.');

                        return;
                    }

                    // Check if user has enough tokens for auto-find based on selected competitor tier
                    $competitorTier = $this->input('competitor_scan_tier', 'basic');
                    $autoFindTokens = $scanService->getCompetitorScanTokenCost($competitorTier);

                    if ($autoFindTokens > 0 && $user->token_balance < $autoFindTokens) {
                        $maxCompetitors = ScanService::MAX_COMPETITORS;
                        $costPerScan = $scanService->getTokenCost($competitorTier);
                        $aiFee = ScanService::AI_DISCOVERY_FEE;
                        $scanCosts = $costPerScan * $maxCompetitors;
                        $breakdown = "{$aiFee} AI discovery";
                        if ($scanCosts > 0) {
                            $breakdown .= " + {$scanCosts} for {$maxCompetitors} scans";
                        }
                        $validator->errors()->add(
                            'auto_find_competitors',
                            "Auto-find competitors with ".ucfirst($competitorTier)." tier requires {$autoFindTokens} tokens ({$breakdown}). You have {$user->token_balance} tokens available."
                        );
                    }
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

    public function isCompetitor(): bool
    {
        return $this->boolean('is_competitor');
    }

    public function shouldAutoFindCompetitors(): bool
    {
        return $this->boolean('auto_find_competitors') && $this->getTier() === 'full';
    }

    public function getCompetitorScanTier(): ?string
    {
        if (! $this->shouldAutoFindCompetitors()) {
            return null;
        }

        return $this->input('competitor_scan_tier', 'basic');
    }

    public function getAutoFindTokenCost(): int
    {
        if (! $this->shouldAutoFindCompetitors()) {
            return 0;
        }

        $scanService = app(ScanService::class);

        return $scanService->getCompetitorScanTokenCost($this->getCompetitorScanTier());
    }
}
