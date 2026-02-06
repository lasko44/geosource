<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cancels the user's active subscription.
 */
class CancelSubscriptionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }

        return redirect()->route('billing.index')
            ->with('success', 'Subscription cancelled. You can still use the service until the end of your billing period.');
    }
}
