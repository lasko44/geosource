<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Processes the marketing email unsubscribe request.
 */
class ProcessUnsubscribeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'reason' => 'nullable|string|max:500',
        ]);

        $email = $request->input('email');
        $reason = $request->input('reason');

        if (MarketingUnsubscribe::isUnsubscribed($email)) {
            return redirect()->route('marketing.unsubscribe.success');
        }

        $user = User::where('email', $email)->first();

        MarketingUnsubscribe::create([
            'email' => $email,
            'user_id' => $user?->id,
            'reason' => $reason,
        ]);

        return redirect()->route('marketing.unsubscribe.success');
    }
}
