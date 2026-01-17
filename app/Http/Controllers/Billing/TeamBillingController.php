<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamBillingController extends Controller
{
    /**
     * Show the team billing dashboard.
     */
    public function index(Request $request, Team $team): Response
    {
        $this->authorize('manageBilling', $team);

        $subscription = $team->subscription('default');

        return Inertia::render('teams/Billing', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'subscription' => $subscription ? [
                'name' => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'ends_at' => $subscription->ends_at?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'cancelled' => $subscription->cancelled(),
                'active' => $subscription->active(),
            ] : null,
            'defaultPaymentMethod' => $team->defaultPaymentMethod() ? [
                'brand' => $team->pm_type,
                'last_four' => $team->pm_last_four,
            ] : null,
            'onTrial' => $team->onTrial(),
            'trialEndsAt' => $team->trial_ends_at?->toISOString(),
        ]);
    }

    /**
     * Show available team plans.
     */
    public function plans(Team $team): Response
    {
        $this->authorize('manageBilling', $team);

        return Inertia::render('teams/Plans', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'plans' => config('billing.plans.team'),
            'currentPlan' => $team->subscription('default')?->stripe_price,
        ]);
    }

    /**
     * Show the checkout page for a team plan.
     */
    public function checkout(Request $request, Team $team, string $plan): Response
    {
        $this->authorize('manageBilling', $team);

        $planConfig = config("billing.plans.team.{$plan}");

        if (! $planConfig) {
            abort(404);
        }

        return Inertia::render('teams/Subscribe', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'plan' => array_merge($planConfig, ['key' => $plan]),
            'intent' => $team->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Subscribe the team to a plan.
     */
    public function subscribe(Request $request, Team $team)
    {
        $this->authorize('manageBilling', $team);

        $request->validate([
            'plan' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $plan = $request->input('plan');
        $planConfig = config("billing.plans.team.{$plan}");

        if (! $planConfig) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        try {
            $team->newSubscription('default', $planConfig['price_id'])
                ->create($request->input('payment_method'));

            return redirect()->route('teams.billing', $team)
                ->with('success', 'Successfully subscribed to '.$planConfig['name'].'!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel the team subscription.
     */
    public function cancel(Request $request, Team $team)
    {
        $this->authorize('manageBilling', $team);

        $subscription = $team->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }

        return redirect()->route('teams.billing', $team)
            ->with('success', 'Subscription cancelled. You can still use the service until the end of your billing period.');
    }

    /**
     * Resume the team subscription.
     */
    public function resume(Request $request, Team $team)
    {
        $this->authorize('manageBilling', $team);

        $subscription = $team->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        return redirect()->route('teams.billing', $team)
            ->with('success', 'Subscription resumed successfully!');
    }

    /**
     * Redirect to Stripe billing portal for team.
     */
    public function portal(Request $request, Team $team)
    {
        $this->authorize('manageBilling', $team);

        return $team->redirectToBillingPortal(route('teams.billing', $team));
    }
}
