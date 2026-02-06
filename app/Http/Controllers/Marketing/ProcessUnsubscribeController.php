<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\UnsubscribeRequest;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Processes the marketing email unsubscribe request.
 */
class ProcessUnsubscribeController extends Controller
{
    public function __invoke(UnsubscribeRequest $request): RedirectResponse
    {
        $email = $request->getEmail();

        if (MarketingUnsubscribe::isUnsubscribed($email)) {
            return redirect()->route('marketing.unsubscribe.success');
        }

        $user = User::where('email', $email)->first();

        MarketingUnsubscribe::create([
            'email' => $email,
            'user_id' => $user?->id,
            'reason' => $request->getReason(),
        ]);

        return redirect()->route('marketing.unsubscribe.success');
    }
}
