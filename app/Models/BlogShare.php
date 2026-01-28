<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogShare extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'blog_post_id',
        'user_id',
        'platform',
        'visitor_hash',
        'ip_address',
        'user_agent',
        'country',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the blog post that was shared.
     */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    /**
     * Get the user who shared the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by platform.
     */
    public function scopeOnPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get platform display name.
     */
    public function getPlatformNameAttribute(): string
    {
        return match ($this->platform) {
            'twitter' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            'facebook' => 'Facebook',
            'copy_link' => 'Copy Link',
            default => ucfirst($this->platform),
        };
    }
}
