<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreIntendedUrl
{
    /**
     * Handle an incoming request.
     *
     * Store the redirect URL from query parameter as the intended URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('redirect')) {
            $redirect = $request->input('redirect');

            // Only allow internal redirects (starting with /)
            if (str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
                session()->put('url.intended', url($redirect));
            }
        }

        return $next($request);
    }
}
