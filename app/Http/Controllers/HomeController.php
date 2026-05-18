<?php

namespace App\Http\Controllers;

use App\Services\ExperimentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Renders the homepage with A/B experiment variant assignment.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, ExperimentService $experimentService): Response
    {
        $visitorId = $request->cookie('ab_visitor');
        $newCookie = false;

        if (! $visitorId) {
            $visitorId = (string) Str::uuid();
            $newCookie = true;
        }

        $variant = $experimentService->assignVariant('homepage_scan_input', $visitorId);

        $response = Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'experiment' => [
                'variant' => $variant ?? 'scan_input',
            ],
        ]);

        if ($newCookie) {
            cookie()->queue(cookie('ab_visitor', $visitorId, 525600, '/', null, null, true, false, 'Lax'));
        }

        return $response;
    }
}
