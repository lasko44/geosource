<?php

namespace App\Http\Controllers\GA4;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA4\SelectPropertyRequest;
use App\Jobs\SyncGA4DataJob;
use App\Services\Analytics\GA4Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * Handles GA4 property selection after OAuth.
 */
class GA4SelectPropertyController extends Controller
{
    /**
     * Select a GA4 property to connect.
     */
    public function __invoke(SelectPropertyRequest $request, GA4Service $ga4Service): RedirectResponse
    {
        $result = $ga4Service->createConnectionFromSession(
            $request->user(),
            $request->getPropertyData()
        );

        if (! $result['success']) {
            return redirect()->route('citations.analytics')
                ->withErrors(['oauth' => Arr::get($result, 'error')]);
        }

        SyncGA4DataJob::dispatch($result['connection']);

        $propertyName = $request->input('property_name');

        return redirect()->route('citations.analytics')
            ->with('success', "Connected to {$propertyName} successfully. Initial data sync has started.");
    }
}
