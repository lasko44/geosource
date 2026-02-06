<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA4\GA4DataRequest;
use App\Models\GA4Connection;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\JsonResponse;

/**
 * Returns referral data from Google Analytics.
 */
class GA4ReferralsController extends Controller
{
    /**
     * Get AI referral data for a connection.
     */
    public function __invoke(GA4DataRequest $request, GA4Connection $connection, GA4Service $ga4Service): JsonResponse
    {
        $days = $ga4Service->validateDaysParam($request->getDays());

        return response()->json([
            'data' => $connection->getAIReferralData($days),
            'summary' => $connection->getAITrafficSummary($days),
        ]);
    }
}
