<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscribeRequest;
use App\Mail\AdminNewSubscriptionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Processes subscription creation with Stripe.
 */
class SubscribeController extends Controller
{
    public function __invoke(SubscribeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $planConfig = $request->getPlanConfig();

        if (! $planConfig) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        try {
            $user->newSubscription('default', $planConfig['price_id'])
                ->create($request->getPaymentMethodId());

            session(['subscribed_plan' => [
                'key' => $request->getPlan(),
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
