<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\AdminNewSubscriptionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Processes subscription creation with Stripe.
 */
class SubscribeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();
        $plan = $request->input('plan');
        $planConfig = config("billing.plans.user.{$plan}");

        if (! $planConfig) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        try {
            $user->newSubscription('default', $planConfig['price_id'])
                ->create($request->input('payment_method'));

            session(['subscribed_plan' => [
                'key' => $plan,
                'name' => $planConfig['name'],
                'price' => $planConfig['price'],
            ]]);

            Mail::to('matt@geosource.ai')->send(
                new AdminNewSubscriptionNotification($user, $planConfig['name'], $planConfig['price'])
            );

            return redirect()->route('billing.thank-you');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
