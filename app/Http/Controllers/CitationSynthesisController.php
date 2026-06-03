<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves the cross-study synthesis page summarizing what consistently
 * predicts AI citation behaviour across the v3, v4/v5, v6, and v7 studies.
 */
class CitationSynthesisController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Research/CitationSynthesis');
    }
}
