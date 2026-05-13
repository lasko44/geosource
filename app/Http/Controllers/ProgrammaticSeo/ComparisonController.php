<?php

namespace App\Http\Controllers\ProgrammaticSeo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves programmatic comparison pages targeting "X vs Y" search queries.
 */
class ComparisonController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $comparisons = config('programmatic-seo.comparisons', []);

        $comparison = collect($comparisons)->firstWhere('slug', $slug);

        if (! $comparison) {
            throw new NotFoundHttpException();
        }

        $allComparisons = collect($comparisons)->map(fn (array $item): array => [
            'slug' => Arr::get($item, 'slug'),
            'title' => Arr::get($item, 'title'),
        ])->all();

        return Inertia::render('ProgrammaticSeo/Comparison', [
            'comparison' => $comparison,
            'allComparisons' => $allComparisons,
        ]);
    }
}
