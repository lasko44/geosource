<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleTeamContext;
use App\Http\Middleware\StoreIntendedUrl;
use App\Http\Middleware\TrackPageViews;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'ab_visitor']);

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
        // Return 404 instead of 403 to prevent resource enumeration attacks.
        // Log the actual 403 for security monitoring. See ADR-014.
        $exceptions->render(function (AuthorizationException $e, $request) {
            Log::warning('Authorization denied (returned as 404)', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'message' => $e->getMessage(),
            ]);

            throw new NotFoundHttpException('Not Found');
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }

            return Inertia::render('Error/NotFound')
                ->toResponse($request)
                ->setStatusCode(404);
        });

        // Never expose database errors to users
        $exceptions->render(function (QueryException $e, $request) {
            Log::error('Database error', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Something went wrong. Please try again later.'], 500);
            }

            return Inertia::render('Error/ServerError')
                ->toResponse($request)
                ->setStatusCode(500);
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
