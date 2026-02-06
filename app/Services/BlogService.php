<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Handles blog-related operations including visitor tracking and content fetching.
 */
class BlogService
{
    /**
     * Create a visitor hash for fingerprinting.
     */
    public function createVisitorHash(Request $request): string
    {
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Get geolocation data from IP address.
     */
    public function getGeoData(?string $ip): array
    {
        if ($this->isLocalIp($ip)) {
            return [];
        }

        try {
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country");

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'country' => $response->json('country'),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail - geolocation is optional
        }

        return [];
    }

    /**
     * Check if IP is a local/private address.
     */
    private function isLocalIp(?string $ip): bool
    {
        if (empty($ip)) {
            return true;
        }

        return $ip === '127.0.0.1'
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.');
    }
}
