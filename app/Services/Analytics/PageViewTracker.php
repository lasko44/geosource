<?php

namespace App\Services\Analytics;

use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PageViewTracker
{
    // Known bot patterns
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'search', 'fetch', 'archive',
        'facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp',
        'telegram', 'discord', 'slack', 'curl', 'wget', 'python',
        'java/', 'ruby', 'perl', 'php/', 'go-http', 'axios', 'node',
        'headless', 'phantom', 'selenium', 'puppeteer', 'playwright',
        'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptimerobot',
    ];

    /**
     * Track a page view.
     */
    public function track(Request $request, Response $response): void
    {
        try {
            $userAgent = $request->userAgent() ?? '';
            $isBot = $this->isBot($userAgent);

            // Parse device info from user agent
            $deviceInfo = $this->parseUserAgent($userAgent);

            // Get geolocation from IP (async in production, you might want to queue this)
            $geoData = $this->getGeoData($request->ip());

            // Determine page type and ID
            [$pageType, $pageId, $pageTitle] = $this->determinePageInfo($request);

            // Parse referrer
            $referrer = $request->header('referer');
            $referrerHost = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;

            // Get or create session ID
            $sessionId = $request->session()->getId() ?? $this->generateSessionId($request);

            // Create visitor hash (fingerprint based on IP + User Agent)
            $visitorHash = $this->createVisitorHash($request);

            PageView::create([
                'session_id' => $sessionId,
                'visitor_hash' => $visitorHash,
                'user_id' => $request->user()?->id,
                'url' => $request->fullUrl(),
                'path' => '/' . ltrim($request->path(), '/'),
                'page_type' => $pageType,
                'page_id' => $pageId,
                'page_title' => $pageTitle,
                'referrer' => $referrer ? substr($referrer, 0, 2048) : null,
                'referrer_host' => $referrerHost,
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_term' => $request->query('utm_term'),
                'utm_content' => $request->query('utm_content'),
                'country' => $geoData['country'] ?? null,
                'country_code' => $geoData['country_code'] ?? null,
                'region' => $geoData['region'] ?? null,
                'city' => $geoData['city'] ?? null,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'browser_version' => $deviceInfo['browser_version'],
                'os' => $deviceInfo['os'],
                'os_version' => $deviceInfo['os_version'],
                'ip_address' => $request->ip(),
                'user_agent' => substr($userAgent, 0, 500),
                'is_bot' => $isBot,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track page view: ' . $e->getMessage());
        }
    }

    /**
     * Determine if the user agent is a bot.
     */
    private function isBot(string $userAgent): bool
    {
        $userAgentLower = strtolower($userAgent);

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse user agent to extract device info.
     */
    private function parseUserAgent(string $userAgent): array
    {
        $result = [
            'device_type' => 'desktop',
            'browser' => null,
            'browser_version' => null,
            'os' => null,
            'os_version' => null,
        ];

        if (empty($userAgent)) {
            return $result;
        }

        // Device type detection
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini|opera mobi/i', $userAgent)) {
            $result['device_type'] = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $result['device_type'] = 'tablet';
        }

        // Browser detection
        if (preg_match('/Firefox\/(\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['browser'] = 'Firefox';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Edg\/(\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['browser'] = 'Edge';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/OPR\/(\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['browser'] = 'Opera';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Chrome\/(\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['browser'] = 'Chrome';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Safari\/(\d+(\.\d+)?)/i', $userAgent, $matches) && !str_contains($userAgent, 'Chrome')) {
            $result['browser'] = 'Safari';
            if (preg_match('/Version\/(\d+(\.\d+)?)/i', $userAgent, $versionMatches)) {
                $result['browser_version'] = $versionMatches[1];
            }
        } elseif (preg_match('/MSIE (\d+(\.\d+)?)/i', $userAgent, $matches) || preg_match('/Trident.*rv:(\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['browser'] = 'Internet Explorer';
            $result['browser_version'] = $matches[1];
        }

        // OS detection
        if (preg_match('/Windows NT (\d+\.\d+)/i', $userAgent, $matches)) {
            $result['os'] = 'Windows';
            $result['os_version'] = $this->mapWindowsVersion($matches[1]);
        } elseif (preg_match('/Mac OS X (\d+[._]\d+([._]\d+)?)/i', $userAgent, $matches)) {
            $result['os'] = 'macOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android (\d+(\.\d+)?)/i', $userAgent, $matches)) {
            $result['os'] = 'Android';
            $result['os_version'] = $matches[1];
        } elseif (preg_match('/iPhone OS (\d+[._]\d+)/i', $userAgent, $matches) || preg_match('/iPad.*OS (\d+[._]\d+)/i', $userAgent, $matches)) {
            $result['os'] = 'iOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $result['os'] = 'Linux';
        }

        return $result;
    }

    /**
     * Map Windows NT version to friendly name.
     */
    private function mapWindowsVersion(string $ntVersion): string
    {
        return match ($ntVersion) {
            '10.0' => '10/11',
            '6.3' => '8.1',
            '6.2' => '8',
            '6.1' => '7',
            '6.0' => 'Vista',
            '5.1', '5.2' => 'XP',
            default => $ntVersion,
        };
    }

    /**
     * Get geolocation data from IP address.
     */
    private function getGeoData(?string $ip): array
    {
        if (empty($ip) || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [];
        }

        try {
            // Use ip-api.com (free, no API key required, 45 requests/min limit)
            $response = \Illuminate\Support\Facades\Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city");

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'country' => $response->json('country'),
                    'country_code' => $response->json('countryCode'),
                    'region' => $response->json('regionName'),
                    'city' => $response->json('city'),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail - geolocation is optional
        }

        return [];
    }

    /**
     * Determine page type, ID, and title from the request.
     */
    private function determinePageInfo(Request $request): array
    {
        $path = $request->path();
        $route = $request->route();

        // Blog posts
        if (preg_match('/^blog\/([^\/]+)$/', $path, $matches)) {
            $slug = $matches[1];
            $post = \App\Models\BlogPost::where('slug', $slug)->first();
            return ['blog', $post?->id, $post?->title];
        }

        // Scan results
        if ($route && $route->getName() === 'scans.show') {
            $scan = $route->parameter('scan');
            return ['scan', $scan?->id, $scan?->title ?? 'Scan Result'];
        }

        // Map common paths to page types
        $pageTypeMap = [
            '/' => 'home',
            'dashboard' => 'dashboard',
            'scans' => 'scans',
            'scans/bulk' => 'bulk_scan',
            'pricing' => 'pricing',
            'blog' => 'blog_index',
            'login' => 'login',
            'register' => 'register',
            'learn' => 'learn',
            'citations' => 'citations',
            'scheduled-scans' => 'scheduled_scans',
            'settings' => 'settings',
            'teams' => 'teams',
        ];

        foreach ($pageTypeMap as $pathPattern => $type) {
            if ($path === $pathPattern || str_starts_with($path, $pathPattern . '/')) {
                return [$type, null, null];
            }
        }

        return ['page', null, null];
    }

    /**
     * Create a visitor hash for fingerprinting.
     */
    private function createVisitorHash(Request $request): string
    {
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Generate a session ID if none exists.
     */
    private function generateSessionId(Request $request): string
    {
        return hash('sha256', $request->ip() . $request->userAgent() . now()->timestamp);
    }
}
