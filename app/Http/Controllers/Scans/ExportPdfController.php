<?php

namespace App\Http\Controllers\Scans;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\ScanService;
use App\Services\TokenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Exports a scan report as a PDF document.
 */
class ExportPdfController extends Controller
{
    public function __invoke(Scan $scan, ScanService $scanService, TokenService $tokenService): Response
    {
        $this->authorize('view', $scan);

        $user = auth()->user();

        if (! $user->hasFeature('pdf_export')) {
            abort(403, 'PDF export is not available on your current plan.');
        }

        if (! $user->is_admin) {
            $tokenCost = config('tokens.costs.pdf_export', 0);
            if ($tokenCost > 0) {
                if (($user->token_balance ?? 0) < $tokenCost) {
                    abort(403, "You need {$tokenCost} tokens to export PDF. You have ".($user->token_balance ?? 0).' tokens.');
                }

                $tokenService->spend($user, 'pdf_export', [
                    'scan_id' => $scan->id,
                    'scan_uuid' => $scan->uuid,
                ]);
            }
        }

        $pdfData = $scanService->preparePdfData($scan, $user);
        $pdf = Pdf::loadView('exports.scan-pdf', $pdfData);

        return $pdf->download($pdfData['filename']);
    }
}
