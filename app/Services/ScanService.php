<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\ScanAuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Handles scan creation, execution, and management with quota validation.
 */
class ScanService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TokenService $tokenService,
    ) {}

    /**
     * Get cooldown minutes based on scan tier.
     */
    public function getCooldownMinutes(string $tier = 'basic'): int
    {
        return match ($tier) {
            'pro', 'full' => 1,
            default => 5,
        };
    }

    /**
     * Check if a URL is on cooldown for a specific tier.
     */
    public function checkCooldown(string $url, int $userId, string $tier): ?array
    {
        $recentScan = Scan::where('url', $url)
            ->where('user_id', $userId)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->first();

        if (! $recentScan) {
            return null;
        }

        $cooldownMinutes = $this->getCooldownMinutes($tier);
        $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);

        if (now()->gte($availableAt)) {
            return null;
        }

        return [
            'scan' => $recentScan,
            'minutes_remaining' => (int) ceil(now()->diffInSeconds($availableAt, false) / 60),
            'available_at' => $availableAt,
        ];
    }

    /**
     * Get token cost for a scan tier.
     */
    public function getTokenCost(string $tier): int
    {
        if ($tier === 'basic') {
            return 0;
        }

        $tokenFeature = $tier === 'full' ? 'scan_full' : 'scan_pro';

        return config("tokens.costs.{$tokenFeature}", 0);
    }

    /**
     * Check if user's subscription covers the requested tier.
     */
    public function subscriptionCoversTier(User $user, string $tier): bool
    {
        if ($user->is_admin || $tier === 'basic') {
            return true;
        }

        return $this->subscriptionService->hasFeature($user, 'scan_'.$tier);
    }

    /**
     * Maximum competitors to discover per scan.
     */
    public const MAX_COMPETITORS = 5;

    /**
     * Base fee for AI competitor discovery (covers API costs, non-refundable).
     */
    public const AI_DISCOVERY_FEE = 3;

    /**
     * Calculate total token cost for auto-find competitors.
     * Includes base AI discovery fee + per-scan costs based on tier.
     */
    public function getCompetitorScanTokenCost(string $competitorTier): int
    {
        $costPerScan = $this->getTokenCost($competitorTier);

        return self::AI_DISCOVERY_FEE + ($costPerScan * self::MAX_COMPETITORS);
    }

    /**
     * Calculate refundable portion of competitor scan tokens (excludes base fee).
     */
    public function getRefundableCompetitorTokens(string $competitorTier, int $competitorsCreated): int
    {
        $costPerScan = $this->getTokenCost($competitorTier);
        $unusedSlots = self::MAX_COMPETITORS - $competitorsCreated;

        return $unusedSlots * $costPerScan;
    }

    /**
     * Execute a single scan with quota and token validation.
     */
    public function executeScan(
        User $user,
        string $url,
        string $tier,
        ?Team $team,
        Request $request,
        bool $isCompetitor = false,
        bool $autoFindCompetitors = false,
        ?string $competitorScanTier = null
    ): Scan {
        return DB::transaction(function () use ($user, $url, $tier, $team, $request, $isCompetitor, $autoFindCompetitors, $competitorScanTier) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $tokensRequired = 0;
            $tokensCharged = false;
            $tokenFeature = null;
            $autoFindTokensCharged = false;

            // Check if tokens needed for scan tier
            if ($tier !== 'basic' && ! $this->subscriptionCoversTier($lockedUser, $tier)) {
                $tokenFeature = $tier === 'full' ? 'scan_full' : 'scan_pro';
                $tokensRequired = config("tokens.costs.{$tokenFeature}", 0);

                if ($tokensRequired > 0 && $lockedUser->token_balance < $tokensRequired) {
                    throw new QuotaExceededException(
                        "You need {$tokensRequired} tokens for a ".ucfirst($tier)." scan. You have {$lockedUser->token_balance} tokens.",
                        'tokens'
                    );
                }
            }

            // Calculate tokens for auto-find competitors based on selected tier
            // Default to 'basic' (free) if not specified
            $autoFindEnabled = $autoFindCompetitors && $tier === 'full';
            $effectiveCompetitorTier = $autoFindEnabled ? ($competitorScanTier ?? 'basic') : null;
            $autoFindTokens = $autoFindEnabled ? $this->getCompetitorScanTokenCost($effectiveCompetitorTier) : 0;
            $totalTokensNeeded = $tokensRequired + $autoFindTokens;

            if ($autoFindTokens > 0 && $lockedUser->token_balance < $totalTokensNeeded) {
                $costPerScan = $this->getTokenCost($effectiveCompetitorTier);
                $scanCosts = $costPerScan * self::MAX_COMPETITORS;
                $breakdown = self::AI_DISCOVERY_FEE.' AI discovery';
                if ($scanCosts > 0) {
                    $breakdown .= ' + '.$scanCosts.' for '.self::MAX_COMPETITORS.' scans';
                }
                throw new QuotaExceededException(
                    "You need {$totalTokensNeeded} tokens ({$tokensRequired} for scan + {$autoFindTokens} for competitors: {$breakdown}). You have {$lockedUser->token_balance} tokens.",
                    'tokens'
                );
            }

            // Check quota
            $this->validateQuota($lockedUser, $team, $request);

            // Deduct tokens for scan tier
            if ($tokenFeature && $tokensRequired > 0) {
                $this->tokenService->spend($lockedUser, $tokenFeature, [
                    'url' => $url,
                    'tier' => $tier,
                ]);
                $tokensCharged = true;
                $lockedUser->refresh();
            }

            // Deduct tokens for auto-find competitors (will be refunded if fewer than 5 found)
            if ($autoFindTokens > 0) {
                $costPerScan = $this->getTokenCost($effectiveCompetitorTier);
                $description = 'Auto-find competitors ('.self::AI_DISCOVERY_FEE.' AI + '.($costPerScan * self::MAX_COMPETITORS).' for '.self::MAX_COMPETITORS.' '.$effectiveCompetitorTier.' scans)';

                $this->tokenService->spendAmount($lockedUser, $autoFindTokens, $description, [
                    'url' => $url,
                    'competitor_tier' => $effectiveCompetitorTier,
                    'max_competitors' => self::MAX_COMPETITORS,
                    'ai_discovery_fee' => self::AI_DISCOVERY_FEE,
                    'scan_cost_each' => $costPerScan,
                ]);
                $autoFindTokensCharged = true;
                $lockedUser->refresh();
            }

            // Create scan
            $scan = Scan::create([
                'user_id' => $lockedUser->id,
                'team_id' => $team?->id,
                'url' => $url,
                'title' => parse_url($url, PHP_URL_HOST),
                'status' => 'pending',
                'requested_tier' => $tier,
                'is_competitor' => $isCompetitor,
                'auto_find_competitors' => $autoFindEnabled,
                'competitor_scan_tier' => $effectiveCompetitorTier,
                'competitor_discovery_status' => $autoFindEnabled ? 'pending' : null,
                'tokens_charged' => $tokensCharged || $autoFindTokensCharged,
                'tokens_amount' => ($tokensCharged ? $tokensRequired : 0) + ($autoFindTokensCharged ? $autoFindTokens : 0),
            ]);

            ScanAuditLog::logScanCreated($scan, $lockedUser, $request);

            return $scan;
        });
    }

    /**
     * Execute a rescan with quota and token validation.
     */
    public function executeRescan(
        Scan $originalScan,
        User $user,
        string $tier,
        Request $request
    ): Scan {
        $team = $originalScan->team_id ? Team::find($originalScan->team_id) : null;

        return DB::transaction(function () use ($originalScan, $user, $tier, $team, $request) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $tokensRequired = 0;
            $tokensCharged = false;
            $tokenFeature = null;

            // Check if tokens needed
            if ($tier !== 'basic' && ! $this->subscriptionCoversTier($lockedUser, $tier)) {
                $tokenFeature = $tier === 'full' ? 'scan_full' : 'scan_pro';
                $tokensRequired = config("tokens.costs.{$tokenFeature}", 0);

                if ($tokensRequired > 0 && $lockedUser->token_balance < $tokensRequired) {
                    throw new QuotaExceededException(
                        "You need {$tokensRequired} tokens for a ".ucfirst($tier)." scan. You have {$lockedUser->token_balance} tokens.",
                        'tokens'
                    );
                }
            }

            // Validate team access if applicable
            if ($team && ! $lockedUser->allTeams()->contains('id', $team->id)) {
                ScanAuditLog::log(ScanAuditLog::EVENT_UNAUTHORIZED_ACCESS, $lockedUser, $originalScan, $team, $request, [
                    'reason' => 'team_access_revoked',
                ]);
                throw new QuotaExceededException('You no longer have access to this team.', 'access');
            }

            // Check quota
            $this->validateQuota($lockedUser, $team, $request, 'rescan');

            // Deduct tokens if needed
            if ($tokenFeature && $tokensRequired > 0) {
                $this->tokenService->spend($lockedUser, $tokenFeature, [
                    'url' => $originalScan->url,
                    'tier' => $tier,
                    'rescan' => true,
                    'original_scan_id' => $originalScan->id,
                ]);
                $tokensCharged = true;
            }

            // Create new scan
            $scan = Scan::create([
                'user_id' => $lockedUser->id,
                'team_id' => $team?->id,
                'url' => $originalScan->url,
                'title' => $originalScan->title ?? parse_url($originalScan->url, PHP_URL_HOST),
                'status' => 'pending',
                'requested_tier' => $tier,
                'tokens_charged' => $tokensCharged,
                'tokens_amount' => $tokensCharged ? $tokensRequired : 0,
            ]);

            ScanAuditLog::logRescan($scan, $originalScan, $lockedUser, $request);

            return $scan;
        });
    }

    /**
     * Execute bulk scans with quota and token validation.
     *
     * @return Scan[]
     */
    public function executeBulkScans(
        User $user,
        array $urls,
        string $tier,
        ?Team $team,
        Request $request
    ): array {
        $scansNeeded = count($urls);

        return DB::transaction(function () use ($user, $urls, $tier, $team, $request, $scansNeeded) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $requiresTokens = false;
            $tokensPerScan = 0;
            $tokenFeature = null;

            // Check if tokens needed
            if ($tier !== 'basic' && ! $this->subscriptionCoversTier($lockedUser, $tier)) {
                $requiresTokens = true;
                $tokenFeature = $tier === 'full' ? 'scan_full' : 'scan_pro';
                $tokensPerScan = config("tokens.costs.{$tokenFeature}", 0);

                $totalTokensNeeded = $tokensPerScan * $scansNeeded;
                if ($lockedUser->token_balance < $totalTokensNeeded) {
                    throw new QuotaExceededException(
                        "You need {$totalTokensNeeded} tokens for {$scansNeeded} ".ucfirst($tier)." scans ({$tokensPerScan} each). You have {$lockedUser->token_balance} tokens.",
                        'tokens'
                    );
                }

                // Deduct all tokens upfront
                foreach (range(1, $scansNeeded) as $i) {
                    $this->tokenService->spend($lockedUser, $tokenFeature, [
                        'bulk_scan' => true,
                        'scan_index' => $i,
                        'total_scans' => $scansNeeded,
                    ]);
                    $lockedUser->refresh();
                }
            }

            // Create all scans
            $createdScans = [];
            foreach ($urls as $url) {
                $scan = Scan::create([
                    'user_id' => $lockedUser->id,
                    'team_id' => $team?->id,
                    'url' => $url,
                    'title' => parse_url($url, PHP_URL_HOST),
                    'status' => 'pending',
                    'requested_tier' => $tier,
                    'tokens_charged' => $requiresTokens && $tokensPerScan > 0,
                    'tokens_amount' => $requiresTokens ? $tokensPerScan : 0,
                ]);

                ScanAuditLog::logScanCreated($scan, $lockedUser, $request);
                $createdScans[] = $scan;
            }

            return $createdScans;
        });
    }

    /**
     * Create a guest scan (no user, no quota/token checks).
     */
    public function executeGuestScan(string $url, string $visitorId): Scan
    {
        return Scan::create([
            'visitor_id' => $visitorId,
            'url' => $url,
            'title' => parse_url($url, PHP_URL_HOST),
            'status' => 'pending',
            'requested_tier' => 'basic',
        ]);
    }

    /**
     * Dispatch scan job.
     */
    public function dispatchScan(Scan $scan): void
    {
        ScanWebsiteJob::dispatch($scan);
    }

    /**
     * Dispatch bulk scan jobs with staggered delays.
     *
     * @param  Scan[]  $scans
     */
    public function dispatchBulkScans(array $scans): void
    {
        foreach ($scans as $index => $scan) {
            ScanWebsiteJob::dispatch($scan)->delay(now()->addSeconds($index * 2));
        }
    }

    /**
     * Validate quota for scan.
     * @throws QuotaExceededException
     */
    protected function validateQuota(User $user, ?Team $team, Request $request, string $action = 'scan'): void
    {
        if ($team) {
            $this->validateTeamQuota($user, $team, $request, $action);
        } else {
            $this->validatePersonalQuota($user, $request, $action);
        }
    }

    /**
     * Validate team scan quota.
     */
    protected function validateTeamQuota(User $user, Team $team, Request $request, string $action): void
    {
        // Lock team owner
        User::where('id', $team->owner_id)->lockForUpdate()->first();

        if (! $this->subscriptionService->canScanForTeam($team)) {
            $usage = $this->subscriptionService->getTeamUsageSummary($team);

            ScanAuditLog::logQuotaExceeded($user, $request, 'team', [
                'team' => $team,
                'team_id' => $team->id,
                'scans_used' => $usage['scans_used'],
                'scans_limit' => $usage['scans_limit'],
                'action' => $action,
            ]);

            throw new QuotaExceededException(
                "This team has reached its monthly scan limit ({$usage['scans_limit']} scans). The team owner needs to upgrade their plan.",
                'team'
            );
        }

        if (! $this->subscriptionService->canMemberScanForTeam($user, $team)) {
            $memberLimit = $this->subscriptionService->getMemberScanLimit($team);
            $memberUsed = $this->subscriptionService->getMemberScansUsedThisMonth($user, $team);

            ScanAuditLog::logQuotaExceeded($user, $request, 'member', [
                'team' => $team,
                'team_id' => $team->id,
                'member_scans_used' => $memberUsed,
                'member_scans_limit' => $memberLimit,
                'action' => $action,
            ]);

            throw new QuotaExceededException(
                "You've reached your personal limit of {$memberLimit} scans per month for this team ({$memberUsed} used). Contact the team owner for assistance.",
                'member'
            );
        }
    }

    /**
     * Validate personal scan quota.
     */
    protected function validatePersonalQuota(User $user, Request $request, string $action): void
    {
        if (! $this->subscriptionService->canScan($user)) {
            $usage = $this->subscriptionService->getUsageSummary($user);

            ScanAuditLog::logQuotaExceeded($user, $request, 'personal', [
                'scans_used' => $usage['scans_used'],
                'scans_limit' => $usage['scans_limit'],
                'action' => $action,
            ]);

            throw new QuotaExceededException(
                "You've reached your monthly scan limit ({$usage['scans_limit']} scans). Please upgrade your plan to continue scanning.",
                'personal'
            );
        }
    }

    /**
     * Check bulk scan quota availability.
     */
    public function checkBulkQuota(User $user, ?Team $team, int $scansNeeded): ?string
    {
        if ($team) {
            $remaining = $this->subscriptionService->getOwnerScansRemaining($team->owner);
            if ($remaining !== -1 && $remaining < $scansNeeded) {
                return "Team only has {$remaining} scans remaining this month. You're trying to scan {$scansNeeded} URLs.";
            }
        } else {
            $remaining = $this->subscriptionService->getScansRemaining($user);
            if ($remaining !== -1 && $remaining < $scansNeeded) {
                return "You only have {$remaining} scans remaining this month. You're trying to scan {$scansNeeded} URLs.";
            }
        }

        return null;
    }

    /**
     * Filter URLs on cooldown.
     *
     * @return array{to_scan: array, on_cooldown: array}
     */
    public function filterCooldownUrls(array $urls, int $userId, string $tier): array
    {
        $cooldownMinutes = $this->getCooldownMinutes($tier);
        $cooldownThreshold = now()->subMinutes($cooldownMinutes);

        $recentlyScannedUrls = Scan::where('user_id', $userId)
            ->whereIn('url', $urls)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', $cooldownThreshold)
            ->pluck('url')
            ->toArray();

        $toScan = [];
        $onCooldown = [];

        foreach ($urls as $url) {
            if (in_array($url, $recentlyScannedUrls)) {
                $onCooldown[] = $url;
            } else {
                $toScan[] = $url;
            }
        }

        return ['to_scan' => $toScan, 'on_cooldown' => $onCooldown];
    }

    /**
     * Get current team from session.
     */
    public function getCurrentTeam(User $user): ?Team
    {
        $currentTeamId = session('current_team_id');

        if ($currentTeamId && $currentTeamId !== 'personal') {
            return $user->allTeams()->firstWhere('id', $currentTeamId);
        }

        return null;
    }

    /**
     * Validate team context matches session.
     */
    public function validateTeamContext(?int $requestTeamId): ?Team
    {
        $storedTeamId = session('current_team_id');
        $sessionIsPersonal = ! $storedTeamId || $storedTeamId === 'personal';
        $requestIsPersonal = $requestTeamId === null;

        if ($sessionIsPersonal !== $requestIsPersonal) {
            return null; // Mismatch
        }

        if (! $requestIsPersonal && (int) $requestTeamId !== (int) $storedTeamId) {
            return null; // Team ID mismatch
        }

        return $requestTeamId ? Team::find($requestTeamId) : null;
    }

    /**
     * Filter pillars based on the scan's requested tier.
     */
    public function filterPillarsForScanTier(array $pillars, Scan $scan, User $user): array
    {
        $userTier = $this->getUserTierForPillars($user);
        $scanTier = $scan->requested_tier ?? 'basic';

        $tierPriority = [
            'basic' => 0,
            'free' => 0,
            'pro' => 1,
            'full' => 2,
            'agency' => 2,
            'agency_member' => 2,
            'admin' => 3,
        ];

        $effectivePriority = max($tierPriority[$userTier] ?? 0, $tierPriority[$scanTier] ?? 0);

        $allowedTiers = ['free'];
        if ($effectivePriority >= 1) {
            $allowedTiers[] = 'pro';
        }
        if ($effectivePriority >= 2) {
            $allowedTiers[] = 'agency';
        }

        return array_filter($pillars, fn ($pillar) => in_array($pillar['tier'] ?? 'free', $allowedTiers));
    }

    /**
     * Get user's tier for pillar filtering.
     */
    public function getUserTierForPillars(User $user): string
    {
        if ($user->is_admin) {
            return 'admin';
        }

        return $this->subscriptionService->getPlanKey($user);
    }

    /**
     * Convert pillar display name to key.
     */
    public function pillarNameToKey(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', $name));
    }

    /**
     * Build scan list query based on team context.
     */
    public function buildScanListQuery(User $user, ?int $currentTeamId): \Illuminate\Database\Eloquent\Builder
    {
        if ($currentTeamId && ($this->subscriptionService->isAgencyTier($user) || $user->is_admin)) {
            $hasAccess = $user->allTeams()->contains('id', $currentTeamId);
            if ($hasAccess) {
                return Scan::where('team_id', $currentTeamId);
            }
        }

        return Scan::where('user_id', $user->id);
    }

    /**
     * Apply search and filter criteria to scan query.
     */
    public function applyScanFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('url', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['grade'])) {
            $query->where('grade', $filters['grade']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Apply sorting to scan query.
     */
    public function applyScanSorting(\Illuminate\Database\Eloquent\Builder $query, string $sortField = 'created_at', string $sortDirection = 'desc'): \Illuminate\Database\Eloquent\Builder
    {
        $allowedSortFields = ['created_at', 'score', 'grade', 'title', 'url'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection);
    }

    /**
     * Validate and return per page value.
     */
    public function getValidatedPerPage(int $perPage): int
    {
        $allowedPerPage = [10, 15, 20, 25, 30, 40, 50];

        return in_array($perPage, $allowedPerPage) ? $perPage : 10;
    }

    /**
     * Get available grades for filter dropdown.
     */
    public function getAvailableGrades(User $user, ?int $currentTeamId): \Illuminate\Support\Collection
    {
        $query = $currentTeamId
            ? Scan::where('team_id', $currentTeamId)
            : Scan::where('user_id', $user->id);

        return $query
            ->whereNotNull('grade')
            ->distinct()
            ->pluck('grade')
            ->sort()
            ->values();
    }

    /**
     * Filter recommendations based on visible pillars and tier limits.
     */
    public function filterRecommendationsForDisplay(array $scanData, User $user): array
    {
        $allRecommendations = $scanData['results']['recommendations'] ?? [];
        $visiblePillarKeys = array_keys($scanData['results']['pillars'] ?? []);

        $allRecommendations = array_filter($allRecommendations, function ($rec) use ($visiblePillarKeys) {
            $pillarKey = $rec['pillar_key'] ?? $this->pillarNameToKey($rec['pillar'] ?? '');

            return in_array($pillarKey, $visiblePillarKeys);
        });
        $allRecommendations = array_values($allRecommendations);

        if ($user->isFreeTier()) {
            $recommendationsLimit = $user->getLimit('recommendations_shown') ?? 3;
            $scanData['results']['recommendations'] = array_slice($allRecommendations, 0, $recommendationsLimit);
            $scanData['results']['recommendations_limited'] = true;
            $scanData['results']['recommendations_total'] = count($allRecommendations);
        } else {
            $scanData['results']['recommendations'] = $allRecommendations;
        }

        return $scanData;
    }

    /**
     * Normalize AI suggestions format for frontend compatibility.
     *
     * Handles both old format (original/improved/explanation) and new format
     * (suggestion/reasoning/example) to ensure consistent frontend display.
     */
    public function normalizeAiSuggestions(array $suggestions): array
    {
        $normalized = array_map(function ($item) {
            return [
                'priority' => Arr::get($item, 'priority', 'medium'),
                'category' => Arr::get($item, 'category', 'general'),
                'suggestion' => Arr::get($item, 'suggestion', Arr::get($item, 'improved', '')),
                'reasoning' => Arr::get($item, 'reasoning', Arr::get($item, 'explanation', '')),
                'example' => Arr::get($item, 'example', ''),
            ];
        }, $suggestions);

        // Filter out any suggestions with empty content
        return array_values(array_filter($normalized, fn ($item) => ! empty($item['suggestion'])));
    }

    /**
     * Build cooldown data for rescan button.
     */
    public function buildCooldownData(Scan $scan, User $user): ?array
    {
        $basicCooldown = $this->checkCooldown($scan->url, $user->id, 'basic');
        $proCooldown = $this->checkCooldown($scan->url, $user->id, 'pro');

        if (! $basicCooldown && ! $proCooldown) {
            return null;
        }

        return [
            'basic' => $basicCooldown ? [
                'minutes_remaining' => $basicCooldown['minutes_remaining'],
                'available_at' => $basicCooldown['available_at']->toIso8601String(),
            ] : null,
            'pro' => $proCooldown ? [
                'minutes_remaining' => $proCooldown['minutes_remaining'],
                'available_at' => $proCooldown['available_at']->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Prepare PDF export data with tier-based restrictions.
     */
    public function preparePdfData(Scan $scan, User $user): array
    {
        $recommendations = $scan->results['recommendations'] ?? [];
        $recommendationsLimited = false;
        $recommendationsTotal = count($recommendations);

        if ($user->isFreeTier()) {
            $recommendationsLimit = $user->getLimit('recommendations_shown') ?? 3;
            $recommendations = array_slice($recommendations, 0, $recommendationsLimit);
            $recommendationsLimited = true;
        }

        $filename = 'geo-scan-'.($scan->title ? str()->slug($scan->title) : $scan->uuid).'.pdf';

        $whiteLabel = [
            'enabled' => false,
            'company_name' => config('app.name'),
            'logo_url' => null,
            'logo_path' => null,
            'primary_color' => '#6366f1',
            'secondary_color' => '#8b5cf6',
            'report_footer' => null,
            'contact_email' => null,
            'website_url' => config('app.url'),
        ];

        if ($scan->team_id && $scan->team) {
            $whiteLabel = $scan->team->getWhiteLabelSettings();
            if ($scan->team->logo_path) {
                $whiteLabel['logo_path'] = storage_path('app/public/'.$scan->team->logo_path);
            }
        }

        $pillars = $this->filterPillarsForScanTier($scan->results['pillars'] ?? [], $scan, $user);

        $visiblePillarNames = array_keys($pillars);
        $recommendations = array_filter($recommendations, function ($rec) use ($visiblePillarNames) {
            $pillarKey = $rec['pillar_key'] ?? $this->pillarNameToKey($rec['pillar'] ?? '');

            return in_array($pillarKey, $visiblePillarNames);
        });
        $recommendations = array_values($recommendations);

        return [
            'scan' => $scan,
            'pillars' => $pillars,
            'recommendations' => $recommendations,
            'summary' => $scan->results['summary'] ?? [],
            'filename' => $filename,
            'recommendationsLimited' => $recommendationsLimited,
            'recommendationsTotal' => $recommendationsTotal,
            'userPlan' => $user->getPlanKey(),
            'generatedAt' => now(),
            'whiteLabel' => $whiteLabel,
        ];
    }

    /**
     * Get current team ID from session.
     */
    public function getCurrentTeamId(): ?int
    {
        $currentTeamId = session('current_team_id');

        return ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;
    }

    /**
     * Cancel a scan.
     */
    public function cancelScan(Scan $scan): bool
    {
        if (! in_array($scan->status, ['pending', 'processing'])) {
            return false;
        }

        $scan->update([
            'status' => 'cancelled',
            'error_message' => 'Scan was cancelled by user.',
        ]);

        return true;
    }

    /**
     * Get scan status data for polling.
     */
    public function getScanStatusData(Scan $scan): array
    {
        $data = [
            'status' => $scan->status,
            'progress_step' => $scan->progress_step,
            'progress_percent' => $scan->progress_percent,
            'title' => $scan->title,
            'error_message' => $scan->error_message,
            'score' => $scan->score,
            'grade' => $scan->grade,
            'auto_find_competitors' => $scan->auto_find_competitors,
            'competitor_discovery_status' => $scan->competitor_discovery_status,
            'competitors_found' => $scan->competitors_found ?? 0,
        ];

        // Include discovered competitors data if discovery is complete or in progress
        if ($scan->auto_find_competitors && $scan->competitor_discovery_status) {
            $data['discovered_competitors'] = $scan->discoveredCompetitors()
                ->select(['uuid', 'url', 'title', 'status', 'score', 'grade'])
                ->get()
                ->map(fn ($c) => [
                    'uuid' => $c->uuid,
                    'url' => $c->url,
                    'title' => $c->title,
                    'status' => $c->status,
                    'score' => $c->score,
                    'grade' => $c->grade,
                ])
                ->toArray();
        }

        return $data;
    }

    /**
     * Get bulk scan status data.
     */
    public function getBulkScanStatus(array $uuids, int $userId): array
    {
        return Scan::whereIn('uuid', $uuids)
            ->where('user_id', $userId)
            ->get()
            ->map(fn ($scan) => [
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'title' => $scan->title,
                'status' => $scan->status,
                'score' => $scan->score,
                'grade' => $scan->grade,
                'error_message' => $scan->error_message,
            ])
            ->toArray();
    }

    /**
     * Format cooldown check response.
     */
    public function formatCooldownResponse(?array $cooldown, string $tier): array
    {
        if ($cooldown) {
            return [
                'on_cooldown' => true,
                'minutes_remaining' => $cooldown['minutes_remaining'],
                'available_at' => $cooldown['available_at']->toIso8601String(),
                'existing_scan_uuid' => $cooldown['scan']->uuid,
                'tier' => $tier,
            ];
        }

        return ['on_cooldown' => false];
    }

    /**
     * Increment email report rate limit counters.
     */
    public function incrementEmailRateLimits(User $user, Scan $scan): void
    {
        if ($user->is_admin) {
            return;
        }

        $cache = app('cache');

        $globalKey = "email_report_hourly:{$user->id}";
        $cache->put($globalKey, $cache->get($globalKey, 0) + 1, now()->addHour());

        $scanKey = "email_report_scan:{$user->id}:{$scan->id}";
        $cache->put($scanKey, $cache->get($scanKey, 0) + 1, now()->addDay());
    }
}
