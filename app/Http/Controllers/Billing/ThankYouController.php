<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the thank you page after successful subscription.
 */
class ThankYouController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $plan = session('subscribed_plan');

        if (! $plan) {
            return redirect()->route('billing.index');
        }

        session()->forget('subscribed_plan');

        return Inertia::render('billing/ThankYou', [
            'plan' => $plan,
        ]);
    }
}
