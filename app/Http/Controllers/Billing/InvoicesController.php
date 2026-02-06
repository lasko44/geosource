<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the user's invoice history.
 */
class InvoicesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('billing/Invoices', [
            'invoices' => $request->user()->invoices()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toISOString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
            ]),
        ]);
    }
}
