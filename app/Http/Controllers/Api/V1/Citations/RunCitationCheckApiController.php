<?php

namespace App\Http\Controllers\Api\V1\Citations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RunCitationCheckApiRequest;
use App\Models\CitationQuery;
use App\Services\Citation\CitationService;
use Illuminate\Http\JsonResponse;

/**
 * Runs a citation check on a specific platform via the API.
 */
class RunCitationCheckApiController extends Controller
{
    public function __invoke(
        RunCitationCheckApiRequest $request,
        CitationQuery $query,
        CitationService $citationService
    ): JsonResponse {
        try {
            $check = $citationService->executeCheck(
                $query,
                $request->input('platform'),
                $request->user()
            );

            return response()->json([
                'uuid' => $check->uuid,
                'platform' => $check->platform,
                'status' => $check->status,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
