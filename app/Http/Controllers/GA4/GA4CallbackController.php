<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGA4DataJob;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles the Google Analytics OAuth callback.
 */
class GA4CallbackController extends Controller
{
    /**
     * Handle the OAuth callback.
     */
    public function __invoke(Request $request, GA4Service $ga4Service): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            Log::warning('GA4 OAuth callback: User not authenticated');

            return redirect()->route('login')
                ->withErrors(['oauth' => 'Your session expired. Please log in and try connecting GA4 again.']);
        }

        $stateError = $ga4Service->validateOAuthState($request->state);
        if ($stateError) {
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => $stateError]);
        }

        $googleError = $ga4Service->checkGoogleOAuthError($request->error);
        if ($googleError) {
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => $googleError]);
        }

        if (! $request->has('code')) {
            Log::warning('GA4 OAuth: No authorization code received');

            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'No authorization code received from Google. Please try again.']);
        }

        try {
            $tokens = $ga4Service->exchangeCode($request->code);

            if (empty(Arr::get($tokens, 'access_token'))) {
                Log::error('GA4 OAuth: Empty access token received');

                return redirect()->route('citations.analytics')
                    ->withErrors(['oauth' => 'Failed to get access token from Google. Please try again.']);
            }

            $ga4Service->storeTokensInSession($tokens);

            $properties = $ga4Service->listProperties(Arr::get($tokens, 'access_token'));

            if (empty($properties)) {
                $ga4Service->getAndClearSessionTokens();

                return redirect()->route('citations.analytics')
                    ->withErrors(['oauth' => 'No GA4 properties found in your Google account.']);
            }

            if (count($properties) > 1) {
                return Inertia::render('Citations/Analytics/SelectProperty', [
                    'properties' => $properties,
                ]);
            }

            $result = $ga4Service->createConnectionFromSession($user, $properties[0]);

            if (! $result['success']) {
                return redirect()->route('citations.analytics')
                    ->withErrors(['oauth' => $result['error']]);
            }

            SyncGA4DataJob::dispatch($result['connection']);

            $propertyName = Arr::get($properties[0], 'property_name');

            return redirect()->route('citations.analytics')
                ->with('success', "Connected to {$propertyName} successfully. Initial data sync has started.");
        } catch (\Exception $e) {
            // Log detailed error for debugging, but don't expose to user
            Log::error('GA4 OAuth callback error', [
                'error_type' => class_basename($e),
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            // Return generic error message to prevent information disclosure
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => 'Failed to connect to Google Analytics. Please try again or contact support.']);
        }
    }
}
