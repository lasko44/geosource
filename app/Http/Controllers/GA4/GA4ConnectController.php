<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Initiates the Google Analytics OAuth connection flow.
 */
class GA4ConnectController extends Controller
{
    /**
     * Start the OAuth flow to connect a GA4 property.
     */
    public function __invoke(Request $request, GA4Service $ga4Service): RedirectResponse
    {
        $user = $request->user();
        $team = $ga4Service->getCurrentTeam($user);

        if (! $ga4Service->canCreateConnection($user, $team)) {
            return back()->withErrors([
                'limit' => 'You have reached your GA4 connection limit. Please upgrade your plan.',
            ]);
        }

        $state = Str::random(40);
        session(['ga4_oauth_state' => $state]);

        return redirect($ga4Service->getAuthorizationUrl($state));
    }
}
