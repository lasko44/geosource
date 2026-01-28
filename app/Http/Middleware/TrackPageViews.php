<?php

namespace App\Http\Middleware;

use App\Services\Analytics\PageViewTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public function __construct(
        private PageViewTracker $tracker
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful page views (not API, not errors)
        if ($this->shouldTrack($request, $response)) {
            $this->tracker->track($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be tracked.
     */
    private function shouldTrack(Request $request, Response $response): bool
    {
        // Only track GET requests
        if ($request->method() !== 'GET') {
            return false;
        }

        // Only track successful responses
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Skip Nova admin panel
        if (str_starts_with($request->path(), 'nova')) {
            return false;
        }

        // Skip API routes
        if (str_starts_with($request->path(), 'api/')) {
            return false;
        }

        // Skip static assets
        $skipExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'map'];
        $extension = pathinfo($request->path(), PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $skipExtensions)) {
            return false;
        }

        // Skip certain paths
        $skipPaths = [
            '_debugbar',
            'livewire',
            'sanctum',
            'broadcasting',
            'vapor-ui',
            '__clockwork',
        ];
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($request->path(), $skipPath)) {
                return false;
            }
        }

        return true;
    }
}
