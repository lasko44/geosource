<?php

namespace App\Nova\Metrics;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Laravel\Nova\Nova;

class PayingCustomers extends Trend
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Paying Customers';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        $range = $request->range ?? 30;

        // Count users who have an active Stripe subscription
        $results = User::query()
            ->select(
                DB::raw("DATE(CONVERT_TZ(users.created_at, '+00:00', '-06:00')) as date"),
                DB::raw('COUNT(*) as aggregate')
            )
            ->whereHas('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->where('users.created_at', '>=', now()->subDays($range)->startOfDay()->utc())
            ->groupBy(DB::raw("DATE(CONVERT_TZ(users.created_at, '+00:00', '-06:00'))"))
            ->orderBy('date')
            ->get()
            ->pluck('aggregate', 'date')
            ->toArray();

        $trend = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[$date] = $results[$date] ?? 0;
        }

        return (new TrendResult)->trend($trend)->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array<int|string, string>
     */
    public function ranges(): array
    {
        return [
            30 => Nova::__('30 Days'),
            60 => Nova::__('60 Days'),
            90 => Nova::__('90 Days'),
            180 => Nova::__('180 Days'),
            365 => Nova::__('365 Days'),
        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'paying-customers';
    }
}
