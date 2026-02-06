<?php

namespace App\Http\Controllers\Nova;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Displays admin documentation pages.
 */
class DocumentationController extends Controller
{
    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        // Ensure user is admin
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        return view('nova.documentation');
    }
}
