<?php

namespace App\Http\Requests\Scans;

use App\Models\Team;
use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBulkScanRequest extends FormRequest
{
    protected array $validUrls = [];

    protected array $invalidUrls = [];

    protected array $urlsOnCooldown = [];

    protected ?Team $validatedTeam = null;

    public function authorize(): bool
    {
        return $this->user()->hasFeature('bulk_scanning');
    }

    public function rules(): array
    {
        return [
            'urls' => 'required|string',
            'tier' => 'nullable|in:basic,pro,full',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $scanService = app(ScanService::class);
                $user = $this->user();

                // Parse URLs
                $urls = array_filter(array_map('trim', explode("\n", $this->urls)));
                $urls = array_unique($urls);

                foreach ($urls as $url) {
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $this->validUrls[] = $url;
                    } else {
                        $this->invalidUrls[] = $url;
                    }
                }

                if (empty($this->validUrls)) {
                    $validator->errors()->add('urls', 'No valid URLs provided.');

                    return;
                }

                if (count($this->validUrls) > 50) {
                    $validator->errors()->add('urls', 'Maximum 50 URLs per batch. You provided '.count($this->validUrls).' URLs.');

                    return;
                }

                // Filter cooldown URLs
                $tier = $this->input('tier', 'basic');
                $filtered = $scanService->filterCooldownUrls($this->validUrls, $user->id, $tier);
                $this->urlsOnCooldown = $filtered['on_cooldown'];
                $this->validUrls = $filtered['to_scan'];

                if (empty($this->validUrls)) {
                    $cooldownMinutes = $scanService->getCooldownMinutes($tier);
                    $count = count($this->urlsOnCooldown);
                    $validator->errors()->add('urls', "All {$count} URL(s) were scanned within the last {$cooldownMinutes} minute(s). Please wait before scanning them again.");

                    return;
                }

                // Get team context
                $currentTeamId = session('current_team_id');
                $teamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

                if ($teamId) {
                    $team = $user->allTeams()->firstWhere('id', $teamId);
                    if (! $team) {
                        $validator->errors()->add('team', 'You do not have access to this team.');

                        return;
                    }
                    $this->validatedTeam = $team;
                }

                // Check quota
                $quotaError = $scanService->checkBulkQuota($user, $this->validatedTeam, count($this->validUrls));
                if ($quotaError) {
                    $validator->errors()->add('quota', $quotaError);
                }
            },
        ];
    }

    public function getValidUrls(): array
    {
        return $this->validUrls;
    }

    public function getInvalidUrls(): array
    {
        return $this->invalidUrls;
    }

    public function getUrlsOnCooldown(): array
    {
        return $this->urlsOnCooldown;
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
