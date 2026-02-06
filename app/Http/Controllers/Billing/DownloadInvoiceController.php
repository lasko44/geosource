<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads a subscription invoice as PDF.
 */
class DownloadInvoiceController extends Controller
{
    public function __invoke(Request $request, string $invoiceId): Response
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    }
}
