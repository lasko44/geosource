<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA4\GA4DataRequest;
use App\Models\GA4Connection;
use App\Services\Analytics\GA4DataSyncService;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\JsonResponse;

/**
 * Returns traffic data from Google Analytics.
 */
class GA4TrafficController extends Controller
{
    /**
     * Get AI traffic summary for a connection.
     */
    public function __invoke(
        GA4DataRequest $request,
        GA4Connection $connection,
        GA4Service $ga4Service,
        GA4DataSyncService $syncService
    ): JsonResponse {
        $days = $ga4Service->validateDaysParam($request->getDays());

        return response()->json([
            'summary' => $connection->getAITrafficSummary($days),
            'trend' => $syncService->getDailyAITrafficTrend($connection, $days),
        ]);
    }
}
