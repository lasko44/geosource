<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $hasTeams = false;
        $canCreateTeams = false;

        if ($user) {
            $subscriptionService = app(SubscriptionService::class);
            // User can view teams if they're agency tier (including team members) or admin
            $hasTeams = $user->is_admin || $subscriptionService->isAgencyTier($user);
            // User can create teams only if they own an Agency subscription (not via team membership)
            $canCreateTeams = $subscriptionService->canCreateTeams($user);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'hasTeams' => $hasTeams,
            'canCreateTeams' => $canCreateTeams,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
