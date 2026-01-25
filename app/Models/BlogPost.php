<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'meta_title',
        'meta_description',
        'author_id',
        'status',
        'published_at',
        'tags',
    ];

    protected $appends = [
        'featured_image_url',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BlogPost $post) {
            $post->uuid = Str::uuid();
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'tags' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at <= now();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return max(1, (int) ceil($words / 200));
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->published_at?->format('F j, Y') ?? 'Draft';
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }

        // Otherwise, generate URL from storage
        return Storage::disk('public')->url($this->featured_image);
    }
}
