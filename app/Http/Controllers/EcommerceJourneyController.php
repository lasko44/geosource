<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Serves the ecommerce recommendation-survival study page. The data is a
 * one-time research snapshot, committed at
 * database/research-data/ecommerce-recommendation-survival.json. Refreshing
 * requires re-running the study locally and running
 * `php artisan research:export`.
 */
class EcommerceJourneyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Research/EcommerceJourneyStudy', $this->loadSnapshot());
    }

    private function loadSnapshot(): array
    {
        $path = base_path('database/research-data/ecommerce-recommendation-survival.json');
        if (! is_file($path)) {
            throw new RuntimeException(
                'Missing research snapshot: database/research-data/ecommerce-recommendation-survival.json. '
                .'Run `php artisan research:export` to regenerate it from the local study DB.'
            );
        }
        $decoded = json_decode(file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Research snapshot file is malformed: '.$path);
        }
        return $decoded;
    }
}
