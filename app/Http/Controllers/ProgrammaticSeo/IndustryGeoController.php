<?php

namespace App\Http\Controllers\ProgrammaticSeo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves programmatic GEO industry pages for AI search visibility by vertical.
 */
class IndustryGeoController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $industries = config('programmatic-seo.industries', []);

        $industry = collect($industries)->firstWhere('slug', $slug);

        if (! $industry) {
            throw new NotFoundHttpException();
        }

        $allIndustries = collect($industries)->map(fn (array $item): array => [
            'slug' => Arr::get($item, 'slug'),
            'name' => Arr::get($item, 'name'),
        ])->all();

        return Inertia::render('ProgrammaticSeo/IndustryGeo', [
            'industry' => $industry,
            'allIndustries' => $allIndustries,
        ]);
    }
}
