<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaignSend;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Illuminate\Http\Request;

class MarketingUnsubscribeController extends Controller
{
    /**
     * Handle the unsubscribe request.
     */
    public function unsubscribe(Request $request)
    {
        // Validate signature
        if (! $request->hasValidSignature()) {
            return view('marketing.unsubscribe-invalid');
        }

        $email = $request->query('email');
        $campaignId = $request->query('campaign');

        if (empty($email)) {
            return view('marketing.unsubscribe-invalid');
        }

        // Check if already unsubscribed
        if (MarketingUnsubscribe::isUnsubscribed($email)) {
            return view('marketing.unsubscribe-already');
        }

        return view('marketing.unsubscribe-confirm', [
            'email' => $email,
            'campaignId' => $campaignId,
        ]);
    }

    /**
     * Process the unsubscribe confirmation.
     */
    public function processUnsubscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reason' => 'nullable|string|max:500',
        ]);

        $email = $request->input('email');
        $reason = $request->input('reason');

        // Check if already unsubscribed
        if (MarketingUnsubscribe::isUnsubscribed($email)) {
            return redirect()->route('marketing.unsubscribe.success');
        }

        // Find the user if they exist
        $user = User::where('email', $email)->first();

        // Create unsubscribe record
        MarketingUnsubscribe::create([
            'email' => $email,
            'user_id' => $user?->id,
            'reason' => $reason,
        ]);

        return redirect()->route('marketing.unsubscribe.success');
    }

    /**
     * Show the success page.
     */
    public function success()
    {
        return view('marketing.unsubscribe-success');
    }

    /**
     * Track email opens via tracking pixel.
     */
    public function trackOpen(Request $request)
    {
        // Validate signature
        if ($request->hasValidSignature()) {
            $sendId = $request->query('send');

            if ($sendId) {
                $send = EmailCampaignSend::find($sendId);
                if ($send && ! $send->opened_at) {
                    $send->update(['opened_at' => now()]);

                    // Update campaign opened count
                    $send->campaign()->increment('opened_count');
                }
            }
        }

        // Return a 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Track link clicks.
     */
    public function trackClick(Request $request)
    {
        // Validate signature
        if ($request->hasValidSignature()) {
            $sendId = $request->query('send');
            $url = $request->query('url');

            if ($sendId) {
                $send = EmailCampaignSend::find($sendId);
                if ($send && ! $send->clicked_at) {
                    $send->update(['clicked_at' => now()]);

                    // Update campaign clicked count
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
