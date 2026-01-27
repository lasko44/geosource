<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LearningResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'category_icon',
        'excerpt',
        'intro',
        'content_type',
        'content',
        'content_blocks',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'json_ld',
        'faq_json_ld',
        'prev_slug',
        'prev_title',
        'next_slug',
        'next_title',
        'related_articles',
        'is_featured',
        'featured_icon',
        'sort_order',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'json_ld' => 'array',
        'faq_json_ld' => 'array',
        'related_articles' => 'array',
        'content_blocks' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Available block types.
     */
    public static function getBlockTypes(): array
    {
        return [
            'heading' => 'Heading',
            'paragraph' => 'Paragraph',
            'list' => 'List (Bulleted/Numbered)',
            'table' => 'Comparison Table',
            'highlight-box' => 'Highlight Box',
            'info-box' => 'Info Box',
            'warning-box' => 'Warning Box',
            'success-box' => 'Success Box',
            'definition' => 'Definition Block',
            'comparison-cards' => 'Comparison Cards (SEO vs GEO)',
            'step-list' => 'Step-by-Step List',
            'checklist' => 'Checklist',
            'code' => 'Code Block',
            'quote' => 'Blockquote',
            'feature-grid' => 'Feature Grid',
            'cta' => 'Call to Action',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($resource) {
            if (empty($resource->slug)) {
                $resource->slug = Str::slug($resource->title);
            }
        });
    }

    /**
     * Scope for published resources.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope for featured resources.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the URL for this resource.
     */
    public function getUrlAttribute(): string
    {
        return url("/resources/{$this->slug}");
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonicalAttribute(): string
    {
        return $this->canonical_url ?: $this->url;
    }

    /**
     * Get related resources.
     */
    public function getRelatedResourcesAttribute()
    {
        if (empty($this->related_articles)) {
            return collect();
        }

        return static::whereIn('slug', $this->related_articles)
            ->published()
            ->get(['id', 'title', 'slug', 'category', 'excerpt']);
    }

    /**
     * Get the previous resource.
     */
    public function getPreviousResourceAttribute()
    {
        if (! $this->prev_slug) {
            return null;
        }

        return static::where('slug', $this->prev_slug)
            ->published()
            ->first(['id', 'title', 'slug']);
    }

    /**
     * Get the next resource.
     */
    public function getNextResourceAttribute()
    {
        if (! $this->next_slug) {
            return null;
        }

        return static::where('slug', $this->next_slug)
            ->published()
            ->first(['id', 'title', 'slug']);
    }

    /**
     * Generate default JSON-LD for the article.
     */
    public function generateJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->meta_title ?: $this->title,
            'description' => $this->meta_description ?: $this->excerpt,
            'url' => $this->url,
            'datePublished' => $this->published_at?->toIso8601String() ?: $this->created_at->toIso8601String(),
            'dateModified' => $this->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => config('app.url'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => config('app.url'),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $this->url,
            ],
        ];
    }

    /**
     * Get the formatted published date.
     */
    public function getFormattedDateAttribute(): string
    {
        $date = $this->published_at ?: $this->created_at;

        return $date->format('F Y');
    }

    /**
     * Available category icons.
     */
    public static function getCategoryIcons(): array
    {
        return [
            'BookOpen' => 'Book Open',
            'Lightbulb' => 'Lightbulb',
            'Code' => 'Code',
            'BarChart' => 'Bar Chart',
            'Target' => 'Target',
            'Shield' => 'Shield',
            'Zap' => 'Zap',
            'FileText' => 'File Text',
            'Search' => 'Search',
            'Settings' => 'Settings',
            'Award' => 'Award',
            'TrendingUp' => 'Trending Up',
            'CheckCircle' => 'Check Circle',
            'List' => 'List',
            'Globe' => 'Globe',
        ];
    }

    /**
     * Available categories.
     */
    public static function getCategories(): array
    {
        return [
            'Foundation' => 'Foundation',
            'Technical' => 'Technical',
            'Comparison' => 'Comparison',
            'Deep Dive' => 'Deep Dive',
            'Metrics' => 'Metrics',
            'Framework' => 'Framework',
            'Trust Signals' => 'Trust Signals',
            'Citations' => 'Citations',
            'Content Strategy' => 'Content Strategy',
            'Content Quality' => 'Content Quality',
            'Media' => 'Media',
        ];
    }
}
