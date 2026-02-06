<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaignSend;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Tracks email opens via a transparent tracking pixel.
 */
class TrackOpenController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->hasValidSignature()) {
            $sendId = $request->query('send');

            if ($sendId) {
                $send = EmailCampaignSend::find($sendId);
                if ($send && ! $send->opened_at) {
                    $send->update(['opened_at' => now()]);
                    $send->campaign()->increment('opened_count');
                }
            }
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
