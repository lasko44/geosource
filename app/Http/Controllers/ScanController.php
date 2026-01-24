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
     * Get cooldown minutes based on user tier.
     * Agency users get 5 minutes, others get 15 minutes.
     */
    private function getCooldownMinutes(User $user): int
    {
        if ($this->subscriptionService->isAgencyTier($user) || $user->is_admin) {
            return 5;
        }

        return 15;
    }

    /**
     * Find recent successful scan for cooldown check.
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
            // Show personal scans (user's own scans, excluding team scans)
            $scanQuery = Scan::where('user_id', $user->id)->whereNull('team_id');
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
            'canBulkScan' => $user->hasFeature('bulk_scanning'),
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

        // Check cooldown - Agency users get 5 min, others get 15 min. Failed scans don't count.
        $cooldownMinutes = $this->getCooldownMinutes($user);
        $recentScan = $this->findRecentScanForCooldown($url, $user->id, $cooldownMinutes);

        if ($recentScan) {
            $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);
            $minutesRemaining = (int) ceil(now()->diffInSeconds($availableAt, false) / 60);

            $minuteWord = $minutesRemaining === 1 ? 'minute' : 'minutes';

            return back()->withErrors([
                'cooldown' => "This URL was scanned recently. Please wait {$minutesRemaining} {$minuteWord} before scanning again.",
            ]);
        }

        // Use transaction with pessimistic locking to prevent race conditions on quota
        try {
            $scan = DB::transaction(function () use ($user, $team, $teamId, $url, $request) {
                // Lock the user row to prevent concurrent quota checks
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

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

                // Create scan record with pending status (inside transaction)
                $scan = Scan::create([
                    'user_id' => $lockedUser->id,
                    'team_id' => $teamId,
                    'url' => $url,
                    'title' => parse_url($url, PHP_URL_HOST),
                    'status' => 'pending',
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
        ]);

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

        // Check cooldown for each URL - Agency users get 5 min, others get 15 min. Failed scans don't count.
        $cooldownMinutes = $this->getCooldownMinutes($user);
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
                'urls' => "All {$count} URL(s) were scanned within the last {$cooldownMinutes} minutes. Please wait before scanning them again.",
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

        // Create scans
        $createdScans = [];

        foreach ($validUrls as $url) {
            $scan = Scan::create([
                'user_id' => $user->id,
                'team_id' => $teamId,
                'url' => $url,
                'title' => parse_url($url, PHP_URL_HOST),
                'status' => 'pending',
            ]);

            ScanAuditLog::logScanCreated($scan, $user, $request);
            ScanWebsiteJob::dispatch($scan);
            $createdScans[] = $scan;
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
     */
    public function checkCooldown(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $user = $request->user();
        $url = $request->input('url');
        $cooldownMinutes = $this->getCooldownMinutes($user);

        $recentScan = $this->findRecentScanForCooldown($url, $user->id, $cooldownMinutes);

        if ($recentScan) {
            $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);
            $secondsRemaining = now()->diffInSeconds($availableAt, false);
            $minutesRemaining = (int) ceil($secondsRemaining / 60);

            if ($minutesRemaining > 0) {
                return response()->json([
                    'on_cooldown' => true,
                    'minutes_remaining' => $minutesRemaining,
                    'available_at' => $availableAt->toIso8601String(),
                    'existing_scan_uuid' => $recentScan->uuid,
                ]);
            }
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

        // Filter pillars based on user's current tier
        if (isset($scanData['results']['pillars'])) {
            $scanData['results']['pillars'] = $this->filterPillarsForTier($scanData['results']['pillars'], $user);
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

        // Check if user can email reports (Pro tier and above)
        $plan = $user->getPlan();
        $canEmailReport = in_array('Email reports', $plan['features'] ?? [])
            || $user->is_admin
            || ! $user->isFreeTier();

        // Check cooldown status for rescan - Agency users get 5 min, others get 15 min. Failed scans don't count.
        $cooldownMinutes = $this->getCooldownMinutes($user);
        $recentScan = $this->findRecentScanForCooldown($scan->url, $user->id, $cooldownMinutes);

        $cooldown = null;
        if ($recentScan) {
            $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);
            $minutesRemaining = (int) ceil(now()->diffInSeconds($availableAt, false) / 60);
            if ($minutesRemaining > 0) {
                $cooldown = [
                    'minutes_remaining' => $minutesRemaining,
                    'available_at' => $availableAt->toIso8601String(),
                ];
            }
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

        // Build base query
        $query = Scan::where('user_id', $user->id);

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

        // Get filter options for the UI
        $grades = Scan::where('user_id', $user->id)
            ->whereNotNull('grade')
            ->distinct()
            ->pluck('grade')
            ->sort()
            ->values();

        // Get current team context for scan form
        $currentTeamId = session('current_team_id');
        $currentTeamId = ($currentTeamId && $currentTeamId !== 'personal') ? (int) $currentTeamId : null;

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
            'canBulkScan' => $user->hasFeature('bulk_scanning'),
            'currentTeamId' => $currentTeamId,
        ]);
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

        // Check cooldown - Agency users get 5 min, others get 15 min. Failed scans don't count.
        $cooldownMinutes = $this->getCooldownMinutes($user);
        $recentScan = $this->findRecentScanForCooldown($scan->url, $user->id, $cooldownMinutes);

        if ($recentScan) {
            $availableAt = $recentScan->created_at->addMinutes($cooldownMinutes);
            $minutesRemaining = (int) ceil(now()->diffInSeconds($availableAt, false) / 60);

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
            $newScan = DB::transaction(function () use ($user, $team, $teamId, $originalScan, $request) {
                // Lock the user row to prevent concurrent quota checks
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

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

                // Create new scan record with pending status (inside transaction)
                $newScan = Scan::create([
                    'user_id' => $lockedUser->id,
                    'team_id' => $teamId,
                    'url' => $originalScan->url,
                    'title' => $originalScan->title ?? parse_url($originalScan->url, PHP_URL_HOST),
                    'status' => 'pending',
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

        // Filter pillars based on user's current tier
        $pillars = $this->filterPillarsForTier($scan->results['pillars'] ?? [], $user);

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
     */
    public function emailReport(Scan $scan, Request $request)
    {
        $this->authorize('view', $scan);

        $user = $request->user();

        // Check if user has email reports feature (Pro tier and above)
        $plan = $user->getPlan();
        $hasEmailReports = in_array('Email reports', $plan['features'] ?? [])
            || $user->is_admin
            || ! $user->isFreeTier();

        if (! $hasEmailReports) {
            return back()->withErrors([
                'email' => 'Email reports are not available on your current plan. Please upgrade to Pro or Agency.',
            ]);
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
