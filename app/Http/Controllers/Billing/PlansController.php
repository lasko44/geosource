<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays available subscription plans.
 */
class PlansController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('billing/Plans', [
            'plans' => config('billing.plans.user'),
            'currentPlan' => $user->subscription('default')?->stripe_price,
            'usage' => $user->getUsageSummary(),
        ]);
    }
}
