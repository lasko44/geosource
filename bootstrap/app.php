<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleTeamContext;
use App\Http\Middleware\StoreIntendedUrl;
use App\Http\Middleware\TrackPageViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);

        $middleware->web(append: [
            StoreIntendedUrl::class,
            HandleAppearance::class,
            HandleTeamContext::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TrackPageViews::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }

            return Inertia::render('Error/NotFound')
                ->toResponse($request)
                ->setStatusCode(404);
        });

        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Too many requests. Please wait a moment before trying again.',
                ], 429);
            }

            // For Inertia requests, redirect back with an error
            return redirect()->back()->withErrors([
                'rate_limit' => 'Too many requests. Please wait a moment before trying again.',
            ]);
        });
    })->create();
