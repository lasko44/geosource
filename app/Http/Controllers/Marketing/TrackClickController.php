<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaignSend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tracks link clicks in marketing emails.
 */
class TrackClickController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->hasValidSignature()) {
            $sendId = $request->query('send');
            $url = $request->query('url');

            if ($sendId) {
                $send = EmailCampaignSend::find($sendId);
                if ($send && ! $send->clicked_at) {
                    $send->update(['clicked_at' => now()]);
                    $send->campaign()->increment('clicked_count');
                }
            }

            if ($url) {
                return redirect()->away($url);
            }
        }

        return redirect()->to(config('app.url'));
    }
}
