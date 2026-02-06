<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Displays the unsubscribe success confirmation page.
 */
class UnsubscribeSuccessController extends Controller
{
    public function __invoke(): View
    {
        return view('marketing.unsubscribe-success');
    }
}
