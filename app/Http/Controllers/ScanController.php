<?php

namespace App\Http\Controllers;

use App\Jobs\ScanWebsiteJob;
use App\Mail\ScanReportMail;
use App\Models\Scan;
use App\Models\ScanAuditLog;
use App\Models\Team;
use App\Models\User;
use App\Services\Citation\CitationService;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\VectorStore;
use App\Services\SubscriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ScanController extends Controller
{
    public function __construct(
        private GeoScorer $geoScorer,
        private EnhancedGeoScorer $enhancedGeoScorer,
        private VectorStore $vectorStore,
        private SubscriptionService $subscriptionService,
        private CitationService $citationService,
    ) {}

    /**
     * Get cooldown minutes based on scan tier.
     * Pro/Full scans: 1 minute cooldown
     * Basic/Free scans: 5 minute cooldown
     */
    private function getCooldownMinutes(string $tier = 'basic'): int
    {
        return match ($tier) {
            'pro', 'full' => 1,
            default => 5,
        };
    }

    /**
     * Check if a URL is on cooldown for a specific tier.
     * If upgrading from basic to pro/full, uses the shorter pro/full cooldown.
     */
    private function checkCooldownForTier(string $url, int $userId, string $requestedTier): ?array
    {
        // Get the most recent scan for this URL
        $recentScan = Scan::where('url', $url)
            ->where('user_id', $userId)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5)) // Max cooldown is 5 mins
            ->orderByDesc('created_at')
            ->first();

        if (!$recentScan) {
            return null; // No recent scan, no cooldown
        }

        // Get the cooldown based on requested tier
        $cooldownMinutes = $this->getCooldownMinutes($requestedTier);
        $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);

        // If cooldown has passed, no restriction
        if (now()->gte($availableAt)) {
            return null;
        }

        $minutesRemaining = (int) ceil(now()->diffInSeconds($availableAt, false) / 60);

        return [
            'scan' => $recentScan,
            'minutes_remaining' => $minutesRemaining,
            'available_at' => $availableAt,
        ];
    }

    /**
     * Find recent successful scan for cooldown check (legacy method for bulk scans).
     * Failed scans don't count towards cooldown.
     */
    private function findRecentScanForCooldown(string $url, int $userId, int $cooldownMinutes): ?Scan
    {
        return Scan::where('url', $url)
            ->where('user_id', $userId)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Display the dashboard with recent scans.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get teams data for users with team access
        $teams = null;
        $currentTeamId = null;
        $currentTeam = null;
        $ownsAnyTeams = false;
        $hasPersonalOption = true;

        if ($this->subscriptionService->isAgencyTier($user) || $user->is_admin) {
            $userTeams = $user->allTeams();
            $ownedTeams = $user->ownedTeams;
            $ownsAnyTeams = $ownedTeams->count() > 0;

            // Users who don't own any teams (just members) should not have personal option
            $hasPersonalOption = $ownsAnyTeams || $user->is_admin;

            $teams = $userTeams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_owner' => $team->owner_id === $user->id,
                'members_count' => $team->members()->count(),
                'role' => $team->getUserRole($user),
            ])->values();

            // Handle team switching
            $requestedTeamId = $request->input('team');
            if ($requestedTeamId === 'personal' && $hasPersonalOption) {
                $currentTeamId = null;
                session(['current_team_id' => 'personal']);
            } elseif ($requestedTeamId && $requestedTeamId !== 'personal') {
                // Verify user has access to this team
                $team = $userTeams->firstWhere('id', $requestedTeamId);
                if ($team) {
                    $currentTeamId = (int) $requestedTeamId;
                    $currentTeam = $team;
                    session(['current_team_id' => $currentTeamId]);
                }
            } else {
                // Use session stored team or default
                $storedTeamId = session('current_team_id');
                if ($storedTeamId && $storedTeamId !== 'personal') {
                    $team = $userTeams->firstWhere('id', $storedTeamId);
                    if ($team) {
                        $currentTeamId = (int) $storedTeamId;
                        $currentTeam = $team;
                    }
                }

                // If no team selected and user doesn't have personal option, auto-select first team
                if (! $currentTeamId && ! $hasPersonalOption && $userTeams->count() > 0) {
                    $firstTeam = $userTeams->first();
                    $currentTeamId = $firstTeam->id;
                    $currentTeam = $firstTeam;
                    session(['current_team_id' => $currentTeamId]);
                }
            }
        }

        // Apply history limit based on plan (use team owner's plan if in team context)
        if ($currentTeam) {
            $historyDays = $currentTeam->owner->getLimit('history_days');
        } else {
            $historyDays = $user->getLimit('history_days');
        }

        // Build scan query based on selected team context
        if ($currentTeamId) {
            // Show team scans (all scans belonging to this team)
            $scanQuery = Scan::where('team_id', $currentTeamId);
        } else {
            // Show personal scans (user's own scans - including team scans they created)
            $scanQuery = Scan::where('user_id', $user->id);
        }

        if ($historyDays !== -1 && $historyDays !== null) {
            $scanQuery->where('created_at', '>=', now()->subDays($historyDays));
        }

        $recentScans = (clone $scanQuery)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = [
            'total_scans' => (clone $scanQuery)->count(),
            'avg_score' => (float) ((clone $scanQuery)->avg('score') ?? 0),
            'best_score' => (float) ((clone $scanQuery)->max('score') ?? 0),
            'scans_this_week' => (clone $scanQuery)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
        ];

        // Get usage summary - use team owner's quota when in team context
        if ($currentTeam) {
            $usage = $this->subscriptionService->getTeamUsageSummary($currentTeam);
        } else {
            $usage = $user->getUsageSummary();
        }

        // Get citation data for Agency tier users
        $citationData = null;
        if ($this->citationService->canAccessCitations($user)) {
            $citationQueries = \App\Models\CitationQuery::where(function ($q) use ($user, $currentTeamId) {
                $q->where('user_id', $user->id);
                if ($currentTeamId) {
                    $q->orWhere('team_id', $currentTeamId);
                }
            })
                ->with(['checks' => function ($q) {
                    $q->latest()->limit(1);
                }])
                ->latest()
                ->limit(5)
                ->get();

            $recentChecks = \App\Models\CitationCheck::where(function ($q) use ($user, $currentTeamId) {
                $q->where('user_id', $user->id);
                if ($currentTeamId) {
                    $q->orWhere('team_id', $currentTeamId);
                }
            })
                ->where('status', 'completed')
                ->latest()
                ->limit(10)
                ->get();

            $citedCount = $recentChecks->where('is_cited', true)->count();
            $totalChecks = $recentChecks->count();

            $citationData = [
                'queries' => $citationQueries->map(fn ($q) => [
                    'id' => $q->id,
                    'uuid' => $q->uuid,
                    'query' => $q->query,
                    'domain' => $q->domain,
                    'is_cited' => $q->checks->first()?->is_cited,
                    'last_checked_at' => $q->last_checked_at?->toISOString(),
                ]),
                'stats' => [
                    'total_queries' => \App\Models\CitationQuery::where(function ($q) use ($user, $currentTeamId) {
                        $q->where('user_id', $user->id);
                        if ($currentTeamId) {
                            $q->orWhere('team_id', $currentTeamId);
                        }
                    })->count(),
                    'cited_count' => $citedCount,
                    'total_checks' => $totalChecks,
                    'citation_rate' => $totalChecks > 0 ? round(($citedCount / $totalChecks) * 100) : 0,
                ],
            ];
        }

        return Inertia::render('Dashboard', [
            'recentScans' => $recentScans,
            'stats' => $stats,
            'usage' => $usage,
            'showUpgradePrompt' => $user->shouldShowUpgradePrompt(),
            'plans' => config('billing.plans.user'),
            'teams' => $teams,
            'currentTeamId' => $currentTeamId,
            'currentTeam' => $currentTeam ? [
                'id' => $currentTeam->id,
                'name' => $currentTeam->name,
                'slug' => $currentTeam->slug,
            ] : null,
            'hasPersonalOption' => $hasPersonalOption,
            'citationData' => $citationData,
            // Token holders or subscribers with bulk scanning can use bulk scan
            'canBulkScan' => $user->hasFeature('bulk_scanning') || ($user->token_balance ?? 0) > 0,
        ]);
    }

    /**
     * Start a new scan.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'team_id' => 'nullable|integer',
            'tier' => 'nullable|in:basic,pro,full',
        ]);

        $user = $request->user();

        // Get team_id from request and session
        $requestTeamId = $request->input('team_id');
        $storedTeamId = session('current_team_id');
        $teamId = null;
        $team = null;

        // Validate team context: request team_id must match session to prevent manipulation
        // If session says personal but request has team_id (or vice versa), reject
        $sessionIsPersonal = ! $storedTeamId || $storedTeamId === 'personal';
        $requestIsPersonal = $requestTeamId === null;

        if ($sessionIsPersonal !== $requestIsPersonal) {
            return back()->withErrors([
                'team_id' => 'Team context mismatch. Please refresh the page and try again.',
            ]);
        }

        if (! $requestIsPersonal) {
            // Validate that request team_id matches session team_id
            if ((int) $requestTeamId !== (int) $storedTeamId) {
                return back()->withErrors([
                    'team_id' => 'Team context mismatch. Please refresh the page and try again.',
                ]);
            }

            // Verify user has access to this team
            $team = $user->allTeams()->firstWhere('id', $requestTeamId);
            if (! $team) {
                return back()->withErrors([
                    'team_id' => 'You do not have access to this team.',
                ]);
            }
            $teamId = (int) $requestTeamId;
        }

        $url = $request->input('url');
        $requestedTier = $request->input('tier', 'basic');

        // Check cooldown based on requested tier
        // Pro/Full scans: 1 minute cooldown, Basic scans: 5 minute cooldown
        $cooldownCheck = $this->checkCooldownForTier($url, $user->id, $requestedTier);

        if ($cooldownCheck) {
            $minutesRemaining = $cooldownCheck['minutes_remaining'];
            $minuteWord = $minutesRemaining === 1 ? 'minute' : 'minutes';

            return back()->withErrors([
                'cooldown' => "This URL was scanned recently. Please wait {$minutesRemaining} {$minuteWord} before scanning again.",
            ]);
        }

        // Use transaction with pessimistic locking to prevent race conditions on quota
        try {
            $scan = DB::transaction(function () use ($user, $team, $teamId, $url, $request, $requestedTier) {
                // Lock the user row to prevent concurrent quota checks
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // Validate and deduct tokens for non-basic tiers if subscription doesn't cover it
                $tokensRequired = 0;
                $tokensCharged = false;
                $tokenFeature = null;

                if ($requestedTier !== 'basic') {
                    // Check if user's subscription covers this tier (not just if they have tokens)
                    $subscriptionIncludesTier = $lockedUser->is_admin ||
                        $this->subscriptionService->hasFeature($lockedUser, 'scan_' . $requestedTier);

                    if (!$subscriptionIncludesTier) {
                        // User needs tokens - check if they have enough
                        $tokenFeature = $requestedTier === 'full' ? 'scan_full' : 'scan_pro';
                        $tokensRequired = config("tokens.costs.{$tokenFeature}", 0);

                        if ($tokensRequired > 0 && $lockedUser->token_balance < $tokensRequired) {
                            throw new \App\Exceptions\QuotaExceededException(
                                "You need {$tokensRequired} tokens for a " . ucfirst($requestedTier) . " scan. You have {$lockedUser->token_balance} tokens. Please purchase more tokens or use a Basic scan.",
                                'tokens'
                            );
                        }
                    }
                }

                // Check scan quota - use team owner's quota if scanning for a team
                if ($team) {
                    // Lock the team owner for quota check
                    $teamOwner = User::where('id', $team->owner_id)->lockForUpdate()->first();

                    // Check team's overall quota (owner's limit)
                    if (! $this->subscriptionService->canScanForTeam($team)) {
                        $usage = $this->subscriptionService->getTeamUsageSummary($team);

                        // Log quota exceeded event
                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'team', [
                            'team' => $team,
                            'team_id' => $team->id,
                            'scans_used' => $usage['scans_used'],
                            'scans_limit' => $usage['scans_limit'],
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "This team has reached its monthly scan limit ({$usage['scans_limit']} scans). The team owner needs to upgrade their plan.",
                            'team'
                        );
                    }

                    // Check per-member limit (prevents one member from exhausting team quota)
                    if (! $this->subscriptionService->canMemberScanForTeam($lockedUser, $team)) {
                        $memberLimit = $this->subscriptionService->getMemberScanLimit($team);
                        $memberUsed = $this->subscriptionService->getMemberScansUsedThisMonth($lockedUser, $team);

                        // Log member limit exceeded event
                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'member', [
                            'team' => $team,
                            'team_id' => $team->id,
                            'member_scans_used' => $memberUsed,
                            'member_scans_limit' => $memberLimit,
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "You've reached your personal limit of {$memberLimit} scans per month for this team ({$memberUsed} used). Contact the team owner for assistance.",
                            'member'
                        );
                    }
                } else {
                    if (! $this->subscriptionService->canScan($lockedUser)) {
                        $usage = $this->subscriptionService->getUsageSummary($lockedUser);

                        // Log quota exceeded event
                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'personal', [
                            'scans_used' => $usage['scans_used'],
                            'scans_limit' => $usage['scans_limit'],
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "You've reached your monthly scan limit ({$usage['scans_limit']} scans). Please upgrade your plan to continue scanning.",
                            'personal'
                        );
                    }
                }

                // Deduct tokens NOW (before scan creation) if required
                // This prevents the TOCTOU vulnerability where tokens are validated but deducted later
                if ($tokenFeature && $tokensRequired > 0) {
                    $tokenService = app(\App\Services\TokenService::class);
                    $tokenService->spend($lockedUser, $tokenFeature, [
                        'url' => $url,
                        'tier' => $requestedTier,
                    ]);
                    $tokensCharged = true;
                }

                // Create scan record with pending status (inside transaction)
                $scan = Scan::create([
                    'user_id' => $lockedUser->id,
                    'team_id' => $teamId,
                    'url' => $url,
                    'title' => parse_url($url, PHP_URL_HOST),
                    'status' => 'pending',
                    'requested_tier' => $requestedTier,
                    'tokens_charged' => $tokensCharged,
                    'tokens_amount' => $tokensCharged ? $tokensRequired : 0,
                ]);

                // Log scan creation
                ScanAuditLog::logScanCreated($scan, $lockedUser, $request);

                return $scan;
            });
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()]);
        }

        // Dispatch the scan job to run asynchronously (outside transaction)
        ScanWebsiteJob::dispatch($scan);

        return redirect()->route('scans.show', $scan);
    }

    /**
     * Show bulk scan page.
     */
    public function bulkIndex(Request $request)
    {
        $user = $request->user();

        // Check if user has bulk scanning feature
        if (! $user->hasFeature('bulk_scanning')) {
            return Inertia::render('Scans/BulkUpgrade', [
                'plans' => config('billing.plans.user'),
            ]);
        }

        // Get current team context
        $currentTeamId = session('current_team_id');
        $currentTeamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

        return Inertia::render('Scans/Bulk', [
            'currentTeamId' => $currentTeamId,
            'usage' => $user->getUsageSummary(),
        ]);
    }

    /**
     * Process bulk URL scanning.
     */
    public function bulkScan(Request $request)
    {
        $user = $request->user();

        // Check if user has bulk scanning feature
        if (! $user->hasFeature('bulk_scanning')) {
            return back()->withErrors(['feature' => 'Bulk scanning is not available on your current plan.']);
        }

        $validated = $request->validate([
            'urls' => 'required|string',
            'tier' => 'nullable|in:basic,pro,full',
        ]);

        $requestedTier = $request->input('tier', 'basic');

        // Parse URLs (one per line)
        $urls = array_filter(array_map('trim', explode("\n", $validated['urls'])));
        $urls = array_unique($urls);

        // Validate URLs
        $validUrls = [];
        $invalidUrls = [];

        foreach ($urls as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $validUrls[] = $url;
            } else {
                $invalidUrls[] = $url;
            }
        }

        if (empty($validUrls)) {
            return back()->withErrors(['urls' => 'No valid URLs provided.']);
        }

        // Limit to 50 URLs per batch
        if (count($validUrls) > 50) {
            return back()->withErrors(['urls' => 'Maximum 50 URLs per batch. You provided '.count($validUrls).' URLs.']);
        }

        // Get requested tier for cooldown calculation
        $requestedTier = $request->input('tier', 'basic');

        // Check cooldown for each URL based on requested tier
        // Pro/Full scans: 1 minute cooldown, Basic scans: 5 minute cooldown
        $cooldownMinutes = $this->getCooldownMinutes($requestedTier);
        $cooldownThreshold = now()->subMinutes($cooldownMinutes);

        $recentlyScannedUrls = Scan::where('user_id', $user->id)
            ->whereIn('url', $validUrls)
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', $cooldownThreshold)
            ->pluck('url')
            ->toArray();

        $urlsOnCooldown = [];
        $urlsToScan = [];

        foreach ($validUrls as $url) {
            if (in_array($url, $recentlyScannedUrls)) {
                $urlsOnCooldown[] = $url;
            } else {
                $urlsToScan[] = $url;
            }
        }

        // If all URLs are on cooldown, return error
        if (empty($urlsToScan)) {
            $count = count($urlsOnCooldown);

            return back()->withErrors([
                'urls' => "All {$count} URL(s) were scanned within the last {$cooldownMinutes} minute(s). Please wait before scanning them again.",
            ]);
        }

        // Update validUrls to only include URLs not on cooldown
        $validUrls = $urlsToScan;

        // Get team context
        $currentTeamId = session('current_team_id');
        $teamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;
        $team = null;

        if ($teamId) {
            $team = $user->allTeams()->firstWhere('id', $teamId);
            if (! $team) {
                return back()->withErrors(['team' => 'You do not have access to this team.']);
            }
        }

        // Check quota for all URLs
        $scansNeeded = count($validUrls);

        if ($team) {
            $remaining = $this->subscriptionService->getOwnerScansRemaining($team->owner);
            if ($remaining !== -1 && $remaining < $scansNeeded) {
                return back()->withErrors([
                    'quota' => "Team only has {$remaining} scans remaining this month. You're trying to scan {$scansNeeded} URLs.",
                ]);
            }
        } else {
            $remaining = $this->subscriptionService->getScansRemaining($user);
            if ($remaining !== -1 && $remaining < $scansNeeded) {
                return back()->withErrors([
                    'quota' => "You only have {$remaining} scans remaining this month. You're trying to scan {$scansNeeded} URLs.",
                ]);
            }
        }

        // Check tokens for non-basic tiers if subscription doesn't cover it
        $tokensPerScan = 0;
        $requiresTokens = false;
        $tokenFeature = null;

        if ($requestedTier !== 'basic') {
            // Check if user's subscription covers this tier (not just if they have tokens)
            $subscriptionIncludesTier = $user->is_admin ||
                $this->subscriptionService->hasFeature($user, 'scan_' . $requestedTier);

            if (!$subscriptionIncludesTier) {
                // User needs tokens
                $requiresTokens = true;
                $tokenFeature = $requestedTier === 'full' ? 'scan_full' : 'scan_pro';
                $tokensPerScan = config("tokens.costs.{$tokenFeature}", 0);
            }
        }

        // Use transaction with pessimistic locking to atomically reserve tokens and create scans
        try {
            $createdScans = DB::transaction(function () use ($user, $teamId, $validUrls, $requestedTier, $requiresTokens, $tokensPerScan, $tokenFeature, $scansNeeded, $request) {
                // Lock the user row to prevent race conditions on token balance
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // Re-check token balance with lock held
                if ($requiresTokens && $tokensPerScan > 0) {
                    $totalTokensNeeded = $tokensPerScan * $scansNeeded;

                    if ($lockedUser->token_balance < $totalTokensNeeded) {
                        $tierName = ucfirst($requestedTier);
                        throw new \App\Exceptions\QuotaExceededException(
                            "You need {$totalTokensNeeded} tokens for {$scansNeeded} {$tierName} scans ({$tokensPerScan} each). You have {$lockedUser->token_balance} tokens.",
                            'tokens'
                        );
                    }

                    // Deduct all tokens upfront (atomic reservation)
                    $tokenService = app(\App\Services\TokenService::class);
                    foreach (range(1, $scansNeeded) as $i) {
                        // Create transaction record for each scan
                        $tokenService->spend($lockedUser, $tokenFeature, [
                            'bulk_scan' => true,
                            'scan_index' => $i,
                            'total_scans' => $scansNeeded,
                        ]);
                        // Refresh user to get updated balance for next spend
                        $lockedUser->refresh();
                    }
                }

                // Create all scans
                $createdScans = [];
                foreach ($validUrls as $index => $url) {
                    $scan = Scan::create([
                        'user_id' => $lockedUser->id,
                        'team_id' => $teamId,
                        'url' => $url,
                        'title' => parse_url($url, PHP_URL_HOST),
                        'status' => 'pending',
                        'requested_tier' => $requestedTier,
                        'tokens_charged' => $requiresTokens && $tokensPerScan > 0,
                        'tokens_amount' => $requiresTokens ? $tokensPerScan : 0,
                    ]);

                    ScanAuditLog::logScanCreated($scan, $lockedUser, $request);
                    $createdScans[] = $scan;
                }

                return $createdScans;
            });

            // Dispatch jobs outside transaction
            foreach ($createdScans as $scan) {
                ScanWebsiteJob::dispatch($scan);
            }
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors(['tokens' => $e->getMessage()]);
        }

        // Return JSON with scan UUIDs for polling
        return response()->json([
            'success' => true,
            'scans' => collect($createdScans)->map(fn ($scan) => [
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'status' => $scan->status,
            ]),
            'skipped' => [
                'cooldown' => count($urlsOnCooldown),
                'invalid' => count($invalidUrls),
            ],
        ]);
    }

    /**
     * Get status of multiple scans (for bulk scan polling).
     */
    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'required|string|uuid',
        ]);

        $user = $request->user();

        $scans = Scan::whereIn('uuid', $validated['uuids'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'scans' => $scans->map(fn ($scan) => [
                'uuid' => $scan->uuid,
                'url' => $scan->url,
                'title' => $scan->title,
                'status' => $scan->status,
                'score' => $scan->score,
                'grade' => $scan->grade,
                'error_message' => $scan->error_message,
            ]),
        ]);
    }

    /**
     * Get scan status for polling.
     */
    public function status(Scan $scan)
    {
        $this->authorize('view', $scan);

        return response()->json([
            'status' => $scan->status,
            'progress_step' => $scan->progress_step,
            'progress_percent' => $scan->progress_percent,
            'title' => $scan->title,
            'error_message' => $scan->error_message,
            'score' => $scan->score,
            'grade' => $scan->grade,
        ]);
    }

    /**
     * Check cooldown status for a URL.
     * Returns cooldown info for each tier so frontend can show appropriate message.
     */
    public function checkCooldown(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'tier' => 'nullable|in:basic,pro,full',
        ]);

        $user = $request->user();
        $url = $request->input('url');
        $requestedTier = $request->input('tier', 'basic');

        // Check cooldown for the requested tier
        $cooldownCheck = $this->checkCooldownForTier($url, $user->id, $requestedTier);

        if ($cooldownCheck) {
            return response()->json([
                'on_cooldown' => true,
                'minutes_remaining' => $cooldownCheck['minutes_remaining'],
                'available_at' => $cooldownCheck['available_at']->toIso8601String(),
                'existing_scan_uuid' => $cooldownCheck['scan']->uuid,
                'tier' => $requestedTier,
            ]);
        }

        return response()->json([
            'on_cooldown' => false,
        ]);
    }

    /**
     * Display scan results.
     */
    public function show(Scan $scan)
    {
        $this->authorize('view', $scan);

        // Load the user who created the scan
        $scan->load('user:id,name');

        $user = auth()->user();
        $scanData = $scan->toArray();

        // Filter pillars based on the scan's requested tier (what the user paid for)
        // This ensures users see all pillars they paid for, whether via subscription or tokens
        if (isset($scanData['results']['pillars'])) {
            $scanData['results']['pillars'] = $this->filterPillarsForScanTier($scanData['results']['pillars'], $scan, $user);
        }

        // Filter recommendations based on visible pillars and tier limits
        if (isset($scanData['results']['recommendations'])) {
            $allRecommendations = $scanData['results']['recommendations'];

            // First, filter recommendations to only include those for visible pillars
            $visiblePillarKeys = array_keys($scanData['results']['pillars'] ?? []);
            $allRecommendations = array_filter($allRecommendations, function ($rec) use ($visiblePillarKeys) {
                $pillarKey = $rec['pillar_key'] ?? $this->pillarNameToKey($rec['pillar'] ?? '');

                return in_array($pillarKey, $visiblePillarKeys);
            });
            $allRecommendations = array_values($allRecommendations);

            // Then apply recommendation limits for free tier users
            if ($user->isFreeTier()) {
                $recommendationsLimit = $user->getLimit('recommendations_shown') ?? 3;
                $scanData['results']['recommendations'] = array_slice($allRecommendations, 0, $recommendationsLimit);
                $scanData['results']['recommendations_limited'] = true;
                $scanData['results']['recommendations_total'] = count($allRecommendations);
            } else {
                $scanData['results']['recommendations'] = $allRecommendations;
            }
        }

        // All users can email reports - content is filtered by scan tier
        $canEmailReport = true;

        // Check cooldown status for rescan - show cooldown for each tier
        // Basic: 5 min, Pro/Full: 1 min
        $cooldown = null;
        $basicCooldown = $this->checkCooldownForTier($scan->url, $user->id, 'basic');
        $proCooldown = $this->checkCooldownForTier($scan->url, $user->id, 'pro');

        if ($basicCooldown || $proCooldown) {
            $cooldown = [
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

        return Inertia::render('Scans/Show', [
            'scan' => $scanData,
            'usage' => $user->getUsageSummary(),
            'canExportPdf' => $user->hasFeature('pdf_export'),
            'canEmailReport' => $canEmailReport,
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * List all scans with sorting and filtering.
     */
    public function list(Request $request)
    {
        $this->authorize('viewAny', Scan::class);

        $user = $request->user();

        // Get current team context
        $currentTeamId = session('current_team_id');
        $currentTeamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

        // Build base query based on team context (same logic as dashboard)
        if ($currentTeamId && ($this->subscriptionService->isAgencyTier($user) || $user->is_admin)) {
            // Verify user has access to this team
            $hasAccess = $user->allTeams()->contains('id', $currentTeamId);
            if ($hasAccess) {
                // Show team scans (all scans belonging to this team)
                $query = Scan::where('team_id', $currentTeamId);
            } else {
                // Fallback to personal scans if no team access
                $query = Scan::where('user_id', $user->id)->whereNull('team_id');
                $currentTeamId = null;
            }
        } else {
            // Show personal scans (all scans created by the user)
            $query = Scan::where('user_id', $user->id);
        }

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'ilike', "%{$search}%")
                  ->orWhere('title', 'ilike', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Apply grade filter
        if ($grade = $request->input('grade')) {
            $query->where('grade', $grade);
        }

        // Apply date range filter
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Apply sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['created_at', 'score', 'grade', 'title', 'url'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Get per page value with validation
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 15, 20, 25, 30, 40, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        // Paginate results with user relationship
        $scans = $query->with('user:id,name')->paginate($perPage)->withQueryString();

        // Get filter options for the UI (use same context as main query)
        $gradesQuery = $currentTeamId
            ? Scan::where('team_id', $currentTeamId)
            : Scan::where('user_id', $user->id);

        $grades = $gradesQuery
            ->whereNotNull('grade')
            ->distinct()
            ->pluck('grade')
            ->sort()
            ->values();

        return Inertia::render('Scans/Index', [
            'scans' => $scans,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'grade' => $request->input('grade', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
            'grades' => $grades,
            'usage' => $user->getUsageSummary(),
            // Token holders or subscribers with bulk scanning can use bulk scan
            'canBulkScan' => $user->hasFeature('bulk_scanning') || ($user->token_balance ?? 0) > 0,
            'currentTeamId' => $currentTeamId,
        ]);
    }

    /**
     * Cancel a pending or processing scan.
     */
    public function cancel(Scan $scan, Request $request)
    {
        $this->authorize('update', $scan);

        // Only allow cancelling pending or processing scans
        if (! in_array($scan->status, ['pending', 'processing'])) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only pending or processing scans can be cancelled.'], 422);
            }

            return back()->withErrors(['status' => 'Only pending or processing scans can be cancelled.']);
        }

        $scan->update([
            'status' => 'cancelled',
            'error_message' => 'Scan was cancelled by user.',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'cancelled',
            ]);
        }

        return back()->with('success', 'Scan cancelled successfully.');
    }

    /**
     * Delete a scan.
     */
    public function destroy(Scan $scan)
    {
        $this->authorize('delete', $scan);

        $scan->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Scan deleted successfully.');
    }

    /**
     * Rescan a URL.
     */
    public function rescan(Scan $scan, Request $request)
    {
        $this->authorize('update', $scan);

        $user = $request->user();
        $requestedTier = $request->input('tier', 'basic');

        // Check cooldown based on requested tier
        // Pro/Full scans: 1 minute cooldown, Basic scans: 5 minute cooldown
        $cooldownCheck = $this->checkCooldownForTier($scan->url, $user->id, $requestedTier);

        if ($cooldownCheck) {
            $minutesRemaining = $cooldownCheck['minutes_remaining'];
            $minuteWord = $minutesRemaining === 1 ? 'minute' : 'minutes';
            $errorMessage = "You can rescan this URL in {$minutesRemaining} {$minuteWord}. Please wait before rescanning.";

            if ($request->wantsJson()) {
                return response()->json(['error' => $errorMessage], 422);
            }

            return redirect()->route('scans.show', $scan)->withErrors([
                'cooldown' => $errorMessage,
            ]);
        }

        // Keep the original team assignment - don't allow switching on rescan
        // This prevents quota confusion attacks
        $teamId = $scan->team_id;
        $team = $teamId ? Team::find($teamId) : null;
        $originalScan = $scan;

        // Use transaction with pessimistic locking to prevent race conditions on quota
        try {
            $newScan = DB::transaction(function () use ($user, $team, $teamId, $originalScan, $request, $requestedTier) {
                // Lock the user row to prevent concurrent quota checks
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // Validate and deduct tokens for non-basic tiers if subscription doesn't cover it
                $tokensRequired = 0;
                $tokensCharged = false;
                $tokenFeature = null;

                if ($requestedTier !== 'basic') {
                    // Check if user's subscription covers this tier (not just if they have tokens)
                    $subscriptionIncludesTier = $lockedUser->is_admin ||
                        $this->subscriptionService->hasFeature($lockedUser, 'scan_' . $requestedTier);

                    if (!$subscriptionIncludesTier) {
                        // User needs tokens - check if they have enough
                        $tokenFeature = $requestedTier === 'full' ? 'scan_full' : 'scan_pro';
                        $tokensRequired = config("tokens.costs.{$tokenFeature}", 0);

                        if ($tokensRequired > 0 && $lockedUser->token_balance < $tokensRequired) {
                            throw new \App\Exceptions\QuotaExceededException(
                                "You need {$tokensRequired} tokens for a " . ucfirst($requestedTier) . " scan. You have {$lockedUser->token_balance} tokens. Please purchase more tokens or use a Basic scan.",
                                'tokens'
                            );
                        }
                    }
                }

                // Check quota based on context (team or personal)
                if ($team) {
                    // Lock the team owner for quota check
                    $teamOwner = User::where('id', $team->owner_id)->lockForUpdate()->first();

                    // For team scans, verify user still has access to the team
                    if (! $lockedUser->allTeams()->contains('id', $teamId)) {
                        ScanAuditLog::log(ScanAuditLog::EVENT_UNAUTHORIZED_ACCESS, $lockedUser, $originalScan, $team, $request, [
                            'reason' => 'team_access_revoked',
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            'You no longer have access to this team.',
                            'access'
                        );
                    }

                    // Check team owner's quota
                    if (! $this->subscriptionService->canScanForTeam($team)) {
                        $usage = $this->subscriptionService->getTeamUsageSummary($team);

                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'team', [
                            'team' => $team,
                            'team_id' => $team->id,
                            'scans_used' => $usage['scans_used'],
                            'scans_limit' => $usage['scans_limit'],
                            'action' => 'rescan',
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "This team has reached its monthly scan limit ({$usage['scans_limit']} scans). The team owner needs to upgrade their plan.",
                            'team'
                        );
                    }

                    // Check per-member limit
                    if (! $this->subscriptionService->canMemberScanForTeam($lockedUser, $team)) {
                        $memberLimit = $this->subscriptionService->getMemberScanLimit($team);
                        $memberUsed = $this->subscriptionService->getMemberScansUsedThisMonth($lockedUser, $team);

                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'member', [
                            'team' => $team,
                            'team_id' => $team->id,
                            'member_scans_used' => $memberUsed,
                            'member_scans_limit' => $memberLimit,
                            'action' => 'rescan',
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "You've reached your personal limit of {$memberLimit} scans per month for this team ({$memberUsed} used). Contact the team owner for assistance.",
                            'member'
                        );
                    }
                } else {
                    // For personal scans, check user's personal quota
                    if (! $this->subscriptionService->canScan($lockedUser)) {
                        $usage = $this->subscriptionService->getUsageSummary($lockedUser);

                        ScanAuditLog::logQuotaExceeded($lockedUser, $request, 'personal', [
                            'scans_used' => $usage['scans_used'],
                            'scans_limit' => $usage['scans_limit'],
                            'action' => 'rescan',
                        ]);

                        throw new \App\Exceptions\QuotaExceededException(
                            "You've reached your monthly scan limit ({$usage['scans_limit']} scans). Please upgrade your plan to continue scanning.",
                            'personal'
                        );
                    }
                }

                // Deduct tokens NOW (before scan creation) if required
                if ($tokenFeature && $tokensRequired > 0) {
                    $tokenService = app(\App\Services\TokenService::class);
                    $tokenService->spend($lockedUser, $tokenFeature, [
                        'url' => $originalScan->url,
                        'tier' => $requestedTier,
                        'rescan' => true,
                        'original_scan_id' => $originalScan->id,
                    ]);
                    $tokensCharged = true;
                }

                // Create new scan record with pending status (inside transaction)
                $newScan = Scan::create([
                    'user_id' => $lockedUser->id,
                    'team_id' => $teamId,
                    'url' => $originalScan->url,
                    'title' => $originalScan->title ?? parse_url($originalScan->url, PHP_URL_HOST),
                    'status' => 'pending',
                    'requested_tier' => $requestedTier,
                    'tokens_charged' => $tokensCharged,
                    'tokens_amount' => $tokensCharged ? $tokensRequired : 0,
                ]);

                // Log rescan event
                ScanAuditLog::logRescan($newScan, $originalScan, $lockedUser, $request);

                return $newScan;
            });
        } catch (\App\Exceptions\QuotaExceededException $e) {
            $errorKey = $e->getQuotaType() === 'access' ? 'access' : 'limit';

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return redirect()->route('scans.show', $scan)->withErrors([$errorKey => $e->getMessage()]);
        }

        // Dispatch the scan job to run asynchronously (outside transaction)
        ScanWebsiteJob::dispatch($newScan);

        // Return JSON for AJAX requests (allows polling for completion)
        if ($request->wantsJson()) {
            return response()->json([
                'uuid' => $newScan->uuid,
                'url' => $newScan->url,
                'status' => $newScan->status,
            ]);
        }

        return redirect()->route('scans.show', $newScan);
    }

    /**
     * Extract title from HTML.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return trim(strip_tags($match[1]));
        }

        return null;
    }

    /**
     * Export scan results as PDF.
     */
    public function exportPdf(Scan $scan)
    {
        $this->authorize('view', $scan);

        $user = auth()->user();

        if (! $user->hasFeature('pdf_export')) {
            abort(403, 'PDF export is not available on your current plan.');
        }

        // Deduct tokens for PDF export (unless admin or subscription includes it)
        if (! $user->is_admin) {
            $tokenCost = config('tokens.costs.pdf_export', 0);
            if ($tokenCost > 0) {
                // Check if user has enough tokens
                if (($user->token_balance ?? 0) < $tokenCost) {
                    abort(403, "You need {$tokenCost} tokens to export PDF. You have " . ($user->token_balance ?? 0) . " tokens.");
                }

                // Deduct tokens atomically
                $tokenService = app(\App\Services\TokenService::class);
                $tokenService->spend($user, 'pdf_export', [
                    'scan_id' => $scan->id,
                    'scan_uuid' => $scan->uuid,
                ]);
            }
        }

        $pdfData = $this->preparePdfData($scan, $user);

        $pdf = Pdf::loadView('exports.scan-pdf', $pdfData);

        return $pdf->download($pdfData['filename']);
    }

    /**
     * Prepare PDF data with tier-based restrictions applied.
     */
    private function preparePdfData(Scan $scan, User $user): array
    {
        $recommendations = $scan->results['recommendations'] ?? [];
        $recommendationsLimited = false;
        $recommendationsTotal = count($recommendations);

        // Apply recommendation limits based on tier
        if ($user->isFreeTier()) {
            $recommendationsLimit = $user->getLimit('recommendations_shown') ?? 3;
            $recommendations = array_slice($recommendations, 0, $recommendationsLimit);
            $recommendationsLimited = true;
        }

        $filename = 'geo-scan-'.($scan->title ? str()->slug($scan->title) : $scan->uuid).'.pdf';

        // Get white label settings from the scan's team
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
            // Get the actual file path for embedding in PDF
            if ($scan->team->logo_path) {
                $whiteLabel['logo_path'] = storage_path('app/public/'.$scan->team->logo_path);
            }
        }

        // Filter pillars based on scan's requested tier (what user paid for)
        $pillars = $this->filterPillarsForScanTier($scan->results['pillars'] ?? [], $scan, $user);

        // Also filter recommendations to only include those for visible pillars
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
     * Filter pillars based on the scan's requested tier.
     * Users see all pillars they paid for, whether via subscription or tokens.
     */
    private function filterPillarsForScanTier(array $pillars, Scan $scan, User $user): array
    {
        // Determine the tier to use for filtering
        // Use the higher of: user's subscription tier OR scan's requested tier
        $userTier = $this->getUserTierForPillars($user);
        $scanTier = $scan->requested_tier ?? 'basic';

        // Map tiers to priority for comparison
        $tierPriority = [
            'basic' => 0,
            'free' => 0,
            'pro' => 1,
            'full' => 2,
            'agency' => 2,
            'agency_member' => 2,
            'admin' => 3,
        ];

        $userPriority = $tierPriority[$userTier] ?? 0;
        $scanPriority = $tierPriority[$scanTier] ?? 0;

        // Use the higher tier (user may have subscription OR paid tokens for this scan)
        $effectivePriority = max($userPriority, $scanPriority);

        $allowedTiers = ['free'];
        if ($effectivePriority >= 1) {
            $allowedTiers[] = 'pro';
        }
        if ($effectivePriority >= 2) {
            $allowedTiers[] = 'agency';
        }

        return array_filter($pillars, function ($pillar) use ($allowedTiers) {
            $pillarTier = $pillar['tier'] ?? 'free';

            return in_array($pillarTier, $allowedTiers);
        });
    }

    /**
     * Filter pillars based on user's current subscription tier.
     */
    private function filterPillarsForTier(array $pillars, User $user): array
    {
        $userTier = $this->getUserTierForPillars($user);

        $allowedTiers = ['free'];
        if (in_array($userTier, ['pro', 'agency', 'agency_member', 'admin'])) {
            $allowedTiers[] = 'pro';
        }
        if (in_array($userTier, ['agency', 'agency_member', 'admin'])) {
            $allowedTiers[] = 'agency';
        }

        return array_filter($pillars, function ($pillar) use ($allowedTiers) {
            $pillarTier = $pillar['tier'] ?? 'free';

            return in_array($pillarTier, $allowedTiers);
        });
    }

    /**
     * Get the user's tier for pillar filtering.
     */
    private function getUserTierForPillars(User $user): string
    {
        if ($user->is_admin) {
            return 'admin';
        }

        return $this->subscriptionService->getPlanKey($user);
    }

    /**
     * Convert pillar display name to key.
     */
    private function pillarNameToKey(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', $name));
    }

    /**
     * Email scan report to user or specified email.
     * Available to all users - email contains only the pillars from their scan tier.
     * Rate limited to prevent spam:
     * - 10 emails per hour per user (global limit)
     * - 3 emails per scan per day (per-scan limit)
     */
    public function emailReport(Scan $scan, Request $request)
    {
        $this->authorize('view', $scan);

        $user = $request->user();

        // Rate limiting (admins exempt)
        if (! $user->is_admin) {
            $cache = app('cache');

            // Global rate limit: 10 emails per hour per user
            $globalKey = "email_report_hourly:{$user->id}";
            $globalCount = (int) $cache->get($globalKey, 0);

            if ($globalCount >= 10) {
                return back()->withErrors([
                    'email' => 'You have reached the hourly email limit (10 emails/hour). Please try again later.',
                ]);
            }

            // Per-scan rate limit: 3 emails per scan per day
            $scanKey = "email_report_scan:{$user->id}:{$scan->id}";
            $scanCount = (int) $cache->get($scanKey, 0);

            if ($scanCount >= 3) {
                return back()->withErrors([
                    'email' => 'You have already sent this report 3 times today. Please try again tomorrow.',
                ]);
            }
        }

        $request->validate([
            'email' => 'nullable|email|max:255',
        ]);

        // Use provided email or default to user's email
        $recipientEmail = $request->input('email', $user->email);

        try {
            \Illuminate\Support\Facades\Log::info('Attempting to send scan report email', [
                'scan_id' => $scan->id,
                'scan_uuid' => $scan->uuid,
                'recipient' => $recipientEmail,
                'mailer' => config('mail.default'),
                'from' => config('mail.from'),
            ]);

            // Send the email with the PDF attachment
            Mail::to($recipientEmail)->send(new ScanReportMail($scan, $user, $recipientEmail));

            // Increment rate limit counters after successful send
            if (! $user->is_admin) {
                $cache = app('cache');

                // Increment global counter (1 hour TTL)
                $globalKey = "email_report_hourly:{$user->id}";
                $cache->put($globalKey, $cache->get($globalKey, 0) + 1, now()->addHour());

                // Increment per-scan counter (24 hour TTL)
                $scanKey = "email_report_scan:{$user->id}:{$scan->id}";
                $cache->put($scanKey, $cache->get($scanKey, 0) + 1, now()->addDay());
            }

            \Illuminate\Support\Facades\Log::info('Scan report email sent successfully', [
                'scan_id' => $scan->id,
                'recipient' => $recipientEmail,
            ]);

            return back()->with('success', "Report sent successfully to {$recipientEmail}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send scan report email', [
                'scan_id' => $scan->id,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'Failed to send email: '.$e->getMessage(),
            ]);
        }
    }
}
