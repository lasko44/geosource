<?php

namespace App\Http\Controllers\Nova;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index(Request $request)
    {
        // Ensure user is admin
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        return view('nova.documentation');
    }
}
