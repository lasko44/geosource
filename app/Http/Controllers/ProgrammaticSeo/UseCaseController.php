<?php

namespace App\Http\Controllers\ProgrammaticSeo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves programmatic "how to" use case pages targeting high-intent AI visibility queries.
 */
class UseCaseController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $useCases = config('programmatic-seo.use_cases', []);

        $useCase = collect($useCases)->firstWhere('slug', $slug);

        if (! $useCase) {
            throw new NotFoundHttpException();
        }

        $allUseCases = collect($useCases)->map(fn (array $item): array => [
            'slug' => Arr::get($item, 'slug'),
            'title' => Arr::get($item, 'title'),
        ])->all();

        return Inertia::render('ProgrammaticSeo/UseCase', [
            'useCase' => $useCase,
            'allUseCases' => $allUseCases,
        ]);
    }
}
