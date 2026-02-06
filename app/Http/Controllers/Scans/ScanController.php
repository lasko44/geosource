<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    /**
     * Display a listing of scans.
     */
    public function index(Request $request, ScanService $scanService): Response
    {
        $this->authorize('viewAny', Scan::class);

        $user = $request->user();
        $currentTeamId = $scanService->getCurrentTeamId();

        $query = $scanService->buildScanListQuery($user, $currentTeamId);

        $query = $scanService->applyScanFilters($query, [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'grade' => $request->input('grade'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query = $scanService->applyScanSorting($query, $sortField, $sortDirection);

        $perPage = $scanService->getValidatedPerPage((int) $request->input('per_page', 10));
        $scans = $query->with('user:id,name')->paginate($perPage)->withQueryString();

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
            'grades' => $scanService->getAvailableGrades($user, $currentTeamId),
            'usage' => $user->getUsageSummary(),
            'canBulkScan' => $user->hasFeature('bulk_scanning') || ($user->token_balance ?? 0) > 0,
            'currentTeamId' => $currentTeamId,
        ]);
    }

    /**
     * Display the specified scan.
     */
    public function show(Scan $scan, ScanService $scanService): Response
    {
        $this->authorize('view', $scan);

        $scan->load('user:id,name');

        $user = auth()->user();
        $scanData = $scan->toArray();

        if (isset($scanData['results']['pillars'])) {
            $scanData['results']['pillars'] = $scanService->filterPillarsForScanTier(
                $scanData['results']['pillars'],
                $scan,
                $user
            );
        }

        if (isset($scanData['results']['recommendations'])) {
            $scanData = $scanService->filterRecommendationsForDisplay($scanData, $user);
        }

        return Inertia::render('Scans/Show', [
            'scan' => $scanData,
            'usage' => $user->getUsageSummary(),
            'canExportPdf' => $user->hasFeature('pdf_export'),
            'canEmailReport' => true,
            'cooldown' => $scanService->buildCooldownData($scan, $user),
        ]);
    }

    /**
     * Store a newly created scan.
     */
    public function store(\App\Http\Requests\Scans\StoreScanRequest $request, ScanService $scanService)
    {
        try {
            $scan = $scanService->executeScan(
                $request->user(),
                $request->url,
                $request->getTier(),
                $request->getValidatedTeam(),
                $request
            );

            $scanService->dispatchScan($scan);

            return redirect()->route('scans.show', $scan);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified scan.
     */
    public function destroy(Scan $scan)
    {
        $this->authorize('delete', $scan);

        $scan->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Scan deleted successfully.');
    }
}
