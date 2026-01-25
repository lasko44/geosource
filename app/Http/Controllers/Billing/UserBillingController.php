<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\AdminNewSubscriptionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class UserBillingController extends Controller
{
    /**
     * Show the billing dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        // Look up plan details by stripe_price
        $planDetails = null;
        if ($subscription) {
            $plans = config('billing.plans.user');
            foreach ($plans as $key => $plan) {
                if ($plan['price_id'] === $subscription->stripe_price) {
                    $planDetails = [
                        'name' => $plan['name'],
                        'price' => $plan['price'],
                        'currency' => $plan['currency'] ?? 'USD',
                        'interval' => $plan['interval'] ?? 'month',
                    ];
                    break;
                }
            }
        }

        return Inertia::render('billing/Index', [
            'subscription' => $subscription ? [
                'name' => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'plan_name' => $planDetails['name'] ?? null,
                'plan_price' => $planDetails['price'] ?? null,
                'plan_currency' => $planDetails['currency'] ?? 'USD',
                'plan_interval' => $planDetails['interval'] ?? 'month',
                'ends_at' => $subscription->ends_at?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'canceled' => $subscription->canceled(),
                'active' => $subscription->active(),
            ] : null,
            'defaultPaymentMethod' => $user->defaultPaymentMethod() ? [
                'brand' => $user->pm_type,
                'last_four' => $user->pm_last_four,
            ] : null,
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trial_ends_at?->toISOString(),
        ]);
    }

    /**
     * Show available plans.
     */
    public function plans(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('billing/Plans', [
            'plans' => config('billing.plans.user'),
            'currentPlan' => $user->subscription('default')?->stripe_price,
            'usage' => $user->getUsageSummary(),
        ]);
    }

    /**
     * Show the checkout page for a plan.
     */
    public function checkout(Request $request, string $plan): Response
    {
        $planConfig = config("billing.plans.user.{$plan}");

        if (! $planConfig) {
            abort(404);
        }

        return Inertia::render('billing/Subscribe', [
            'plan' => array_merge($planConfig, ['key' => $plan]),
            'intent' => $request->user()->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Subscribe the user to a plan.
     */
    public function subscribe(Request $request)
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

            // Store plan info in session for the thank you page
            session(['subscribed_plan' => [
                'key' => $plan,
                'name' => $planConfig['name'],
                'price' => $planConfig['price'],
            ]]);

            // Notify admin of new subscription
            Mail::to('matt@geosource.ai')->send(
                new AdminNewSubscriptionNotification($user, $planConfig['name'], $planConfig['price'])
            );

            return redirect()->route('billing.thank-you');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show the thank you page after successful subscription.
     */
    public function thankYou(Request $request): Response|RedirectResponse
    {
        $plan = session('subscribed_plan');

        // If no plan in session, redirect to billing
        if (! $plan) {
            return redirect()->route('billing.index');
        }

        // Clear the session data so refreshing doesn't re-trigger tracking
        session()->forget('subscribed_plan');

        return Inertia::render('billing/ThankYou', [
            'plan' => $plan,
        ]);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(Request $request)
    {
        $subscription = $request->user()->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }

        return redirect()->route('billing.index')
            ->with('success', 'Subscription cancelled. You can still use the service until the end of your billing period.');
    }

    /**
     * Resume the subscription.
     */
    public function resume(Request $request)
    {
        $subscription = $request->user()->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        return redirect()->route('billing.index')
            ->with('success', 'Subscription resumed successfully!');
    }

    /**
     * Show payment methods.
     */
    public function paymentMethods(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('billing/PaymentMethods', [
            'paymentMethods' => $user->paymentMethods()->map(fn ($method) => [
                'id' => $method->id,
                'brand' => $method->card->brand,
                'last_four' => $method->card->last4,
                'exp_month' => $method->card->exp_month,
                'exp_year' => $method->card->exp_year,
                'is_default' => $method->id === $user->defaultPaymentMethod()?->id,
            ]),
            'intent' => $user->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Add a new payment method.
     */
    public function addPaymentMethod(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $user->addPaymentMethod($request->input('payment_method'));

            if (! $user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($request->input('payment_method'));
            }

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Payment method added successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Set a payment method as default.
     */
    public function setDefaultPaymentMethod(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $request->user()->updateDefaultPaymentMethod($request->input('payment_method'));

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Default payment method updated!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $paymentMethod = $user->findPaymentMethod($request->input('payment_method'));

            if ($paymentMethod) {
                $paymentMethod->delete();
            }

            return redirect()->route('billing.payment-methods')
                ->with('success', 'Payment method removed!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show invoices.
     */
    public function invoices(Request $request): Response
    {
        return Inertia::render('billing/Invoices', [
            'invoices' => $request->user()->invoices()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toISOString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
            ]),
        ]);
    }

    /**
     * Download an invoice.
     */
    public function downloadInvoice(Request $request, string $invoiceId)
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    }

    /**
     * Redirect to Stripe billing portal.
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }
}
