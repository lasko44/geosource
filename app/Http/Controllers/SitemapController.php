<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate XML sitemap for public pages.
     */
    public function index(): Response
    {
        $pages = [
            [
                'url' => '/',
                'lastmod' => '2025-01-20',
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'url' => '/pricing',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ],
            [
                'url' => '/resources',
                'lastmod' => '2025-01-20',
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'url' => '/definitions',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ],
            [
                'url' => '/geo-score-explained',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/geo-optimization-checklist',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/ai-search-visibility-guide',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/what-is-geo',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/geo-vs-seo',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/how-ai-search-works',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/how-llms-cite-sources',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/what-is-a-geo-score',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/geo-content-framework',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/why-llms-txt-matters',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'url' => '/resources/why-ssr-matters-for-geo',
                'lastmod' => '2025-01-20',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $page) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.config('app.url').$page['url'].'</loc>'."\n";
            $xml .= '    <lastmod>'.$page['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$page['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$page['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
