<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scans\EmailScanReportRequest;
use App\Mail\ScanReportMail;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailReportController extends Controller
{
    public function __invoke(EmailScanReportRequest $request, Scan $scan, ScanService $scanService)
    {
        $user = $request->user();
        $recipientEmail = $request->getRecipientEmail();

        try {
            Log::info('Attempting to send scan report email', [
                'scan_id' => $scan->id,
                'scan_uuid' => $scan->uuid,
                'recipient' => $recipientEmail,
            ]);

            Mail::to($recipientEmail)->send(new ScanReportMail($scan, $user, $recipientEmail));

            $scanService->incrementEmailRateLimits($user, $scan);

            Log::info('Scan report email sent successfully', [
                'scan_id' => $scan->id,
                'recipient' => $recipientEmail,
            ]);

            return back()->with('success', "Report sent successfully to {$recipientEmail}");
        } catch (\Exception $e) {
            Log::error('Failed to send scan report email', [
                'scan_id' => $scan->id,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send email: '.$e->getMessage()]);
        }
    }
}
