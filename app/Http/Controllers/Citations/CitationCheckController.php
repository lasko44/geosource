<?php

namespace App\Http\Controllers\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citations\RunBulkCitationCheckRequest;
use App\Http\Requests\Citations\RunCitationCheckRequest;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use App\Support\ErrorSanitizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Handles citation check operations for platforms.
 */
class CitationCheckController extends Controller
{
    /**
     * Run a single citation check.
     */
    public function store(RunCitationCheckRequest $request, CitationQuery $query, CitationService $citationService): \Illuminate\Http\RedirectResponse
    {
        try {
            $check = $citationService->executeCheck(
                $query,
                $request->platform,
                $request->user()
            );

            return redirect()->route('citations.queries.show', $query)
                ->with('activeCheck', $check->uuid);
        } catch (\RuntimeException $e) {
            Log::error('Citation check failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['tokens' => ErrorSanitizer::sanitize($e)]);
        }
    }

    /**
     * Run bulk citation checks.
     */
    public function storeBulk(RunBulkCitationCheckRequest $request, CitationQuery $query, CitationService $citationService): \Illuminate\Http\RedirectResponse
    {
        try {
            $checks = $citationService->executeBulkChecks(
                $query,
                $request->getValidatedPlatforms(),
                $request->user()
            );

            return redirect()->route('citations.queries.show', $query)
                ->with('success', count($checks).' checks started (staggered to avoid rate limits).');
        } catch (\RuntimeException $e) {
            Log::error('Bulk citation check failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['tokens' => ErrorSanitizer::sanitize($e)]);
        }
    }

    /**
     * Get check status for polling.
     *
     * @throws AuthorizationException
     */
    public function show(CitationCheck $check): JsonResponse
    {
        $this->authorize('view', $check);

        return response()->json([
            'status' => $check->status,
            'progress_step' => $check->progress_step,
            'progress_percent' => $check->progress_percent,
            'is_cited' => $check->is_cited,
            'error_message' => $check->error_message,
            'citations' => $check->citations,
            'completed_at' => $check->completed_at?->toISOString(),
        ]);
    }
}
