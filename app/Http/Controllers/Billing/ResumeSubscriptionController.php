<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resumes a cancelled subscription during grace period.
 */
class ResumeSubscriptionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        return redirect()->route('billing.index')
            ->with('success', 'Subscription resumed successfully!');
    }
}
