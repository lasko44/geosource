<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingUnsubscribe;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Displays the marketing email unsubscribe confirmation page.
 */
class ShowUnsubscribeController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (! $request->hasValidSignature()) {
            return view('marketing.unsubscribe-invalid');
        }

        $email = $request->query('email');
        $campaignId = $request->query('campaign');

        if (empty($email)) {
            return view('marketing.unsubscribe-invalid');
        }

        if (MarketingUnsubscribe::isUnsubscribed($email)) {
            return view('marketing.unsubscribe-already');
        }

        return view('marketing.unsubscribe-confirm', [
            'email' => $email,
            'campaignId' => $campaignId,
        ]);
    }
}
