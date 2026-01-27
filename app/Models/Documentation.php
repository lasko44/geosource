<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Documentation extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'category_slug',
        'excerpt',
        'content',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($doc) {
            if (empty($doc->slug)) {
                $doc->slug = Str::slug($doc->title);
            }
            if (empty($doc->category_slug)) {
                $doc->category_slug = Str::slug($doc->category);
            }
        });

        static::updating(function ($doc) {
            if ($doc->isDirty('category')) {
                $doc->category_slug = Str::slug($doc->category);
            }
        });
    }

    /**
     * Scope for published documentation.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for ordering by category and sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category_slug')->orderBy('sort_order');
    }

    /**
     * Scope for searching documentation.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = '%' . $term . '%';

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'ilike', $term)
              ->orWhere('content', 'ilike', $term)
              ->orWhere('category', 'ilike', $term)
              ->orWhere('excerpt', 'ilike', $term);
        });
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        return [
            'getting-started' => 'Getting Started',
            'geo-scoring' => 'GEO Scoring System',
            'citation-tracking' => 'Citation Tracking',
            'billing' => 'Billing & Subscriptions',
            'services' => 'Key Services',
            'models' => 'Database Models',
            'jobs' => 'Jobs & Queue',
            'debugging' => 'Debugging Guide',
            'security' => 'Security',
            'content-management' => 'Content Management',
            'learning-resources' => 'Learning Resources',
            'analytics' => 'Analytics',
        ];
    }

    /**
     * Get grouped documentation by category.
     */
    public static function getGroupedByCategory(): array
    {
        return static::published()
            ->ordered()
            ->get()
            ->groupBy('category')
            ->toArray();
    }
}
