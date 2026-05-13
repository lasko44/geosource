<?php

namespace App\Http\Controllers\ProgrammaticSeo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves programmatic pages about optimizing content for specific AI platforms.
 */
class PlatformOptimizeController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $platforms = config('programmatic-seo.platforms', []);

        $platform = collect($platforms)->firstWhere('slug', $slug);

        if (! $platform) {
            throw new NotFoundHttpException();
        }

        $allPlatforms = collect($platforms)->map(fn (array $item): array => [
            'slug' => Arr::get($item, 'slug'),
            'name' => Arr::get($item, 'name'),
        ])->all();

        return Inertia::render('ProgrammaticSeo/PlatformOptimize', [
            'platform' => $platform,
            'allPlatforms' => $allPlatforms,
        ]);
    }
}
