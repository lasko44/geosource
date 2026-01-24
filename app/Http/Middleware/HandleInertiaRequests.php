<?php

namespace App\Http\Middleware;

use App\Models\Team;
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
        $hasCitationAccess = false;
        $teamBranding = null;

        if ($user) {
            $subscriptionService = app(SubscriptionService::class);
            // User can view teams if they're agency tier (including team members) or admin
            $hasTeams = $user->is_admin || $subscriptionService->isAgencyTier($user);
            // User can create teams only if they own an Agency subscription (not via team membership)
            $canCreateTeams = $subscriptionService->canCreateTeams($user);
            // Citation access is agency-only (same as teams for now)
            $hasCitationAccess = $user->is_admin || $subscriptionService->isAgencyTier($user);

            // Get team branding if user is in a team context with white-label enabled
            $teamBranding = $this->getTeamBranding($request, $user);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'timezone' => $user->timezone ?? 'UTC',
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'hasTeams' => $hasTeams,
            'canCreateTeams' => $canCreateTeams,
            'hasCitationAccess' => $hasCitationAccess,
            'teamBranding' => $teamBranding,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Get team branding settings if user is in a team context with white-label enabled.
     */
    private function getTeamBranding(Request $request, $user): ?array
    {
        $currentTeamId = session('current_team_id');

        if (! $currentTeamId || $currentTeamId === 'personal') {
            return null;
        }

        $team = Team::find($currentTeamId);

        if (! $team || ! $team->hasMember($user)) {
            return null;
        }

        if (! $team->hasWhiteLabel()) {
            return null;
        }

        $settings = $team->getWhiteLabelSettings();

        return [
            'enabled' => $settings['enabled'],
            'teamName' => $team->name,
            'companyName' => $settings['company_name'],
            'logoUrl' => $settings['logo_url'],
            'primaryColor' => $settings['primary_color'],
            'secondaryColor' => $settings['secondary_color'],
            'contactEmail' => $settings['contact_email'],
            'websiteUrl' => $settings['website_url'],
        ];
    }
}
