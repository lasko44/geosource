<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves the /research index page. Lists the original research studies
 * GeoSource has published. Underlying study pages stay at their existing
 * /blog/{slug} URLs for backwards compatibility with already-shared links.
 */
class ResearchIndexController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Research/Index');
    }
}
