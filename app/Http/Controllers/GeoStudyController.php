<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves the public GEO effectiveness research study page.
 */
class GeoStudyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Research/GeoCitationStudy');
    }
}
