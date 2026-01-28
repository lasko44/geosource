<?php

namespace App\Nova\Metrics;

use App\Models\GeoStudy;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class GeoStudyCategoryBreakdown extends Partition
{
    public $name = 'Studies by Category';

    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, GeoStudy::class, 'category')
            ->label(function ($value) {
                return match ($value) {
                    'news' => 'News',
                    'ecommerce' => 'E-commerce',
                    'saas' => 'SaaS',
                    'blog' => 'Blog',
                    'mixed' => 'Mixed',
                    default => ucfirst($value),
                };
            })
            ->colors([
                'news' => '#3b82f6',
                'ecommerce' => '#10b981',
                'saas' => '#8b5cf6',
                'blog' => '#f59e0b',
                'mixed' => '#6b7280',
            ]);
    }

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'geo-study-category-breakdown';
    }
}
