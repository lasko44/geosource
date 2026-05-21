<?php

namespace App\Nova\Metrics;

use App\Models\PageView;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Shows page view distribution by page type.
 */
class PageViewsByType extends Partition
{
    public $name = 'Views by Page Type';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $results = PageView::where('is_bot', false)
            ->whereNotNull('engaged_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('page_type, count(*) as count')
            ->groupBy('page_type')
            ->orderByDesc('count')
            ->limit(15)
            ->pluck('count', 'page_type')
            ->mapWithKeys(function (int $count, string $type): array {
                $labels = [
                    'home' => 'Homepage',
                    'blog' => 'Blog Post',
                    'blog_index' => 'Blog Index',
                    'pricing' => 'Pricing',
                    'industry_geo' => 'Industry GEO',
                    'platform_optimize' => 'Platform Optimize',
                    'comparison' => 'Comparison',
                    'how_to' => 'How-To Guide',
                    'resource_article' => 'Resource Article',
                    'resource_hub' => 'Resource Hub',
                    'geo_page' => 'GEO Page',
                    'scan' => 'Scan Result',
                    'scans' => 'Scans',
                    'dashboard' => 'Dashboard',
                    'login' => 'Login',
                    'register' => 'Register',
                    'citations' => 'Citations',
                    'settings' => 'Settings',
                    'page' => 'Other',
                ];

                return [$labels[$type] ?? ucfirst(str_replace('_', ' ', $type)) => $count];
            })
            ->toArray();

        return $this->result($results)->colors([
            'Homepage' => '#6366f1',
            'Blog Post' => '#22c55e',
            'Industry GEO' => '#f59e0b',
            'Platform Optimize' => '#3b82f6',
            'Comparison' => '#ec4899',
            'How-To Guide' => '#8b5cf6',
            'Resource Article' => '#14b8a6',
            'Pricing' => '#ef4444',
            'Scan Result' => '#06b6d4',
            'Dashboard' => '#64748b',
        ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): \DateInterval
    {
        return new \DateInterval('PT5M');
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'page-views-by-type';
    }
}
