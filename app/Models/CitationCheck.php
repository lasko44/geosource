<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CitationCheck extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'citation_query_id',
        'user_id',
        'team_id',
        'platform',
        'status',
        'progress_step',
        'progress_percent',
        'is_cited',
        'ai_response',
        'citations',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    // AI Platform constants
    public const PLATFORM_PERPLEXITY = 'perplexity';
    public const PLATFORM_OPENAI = 'openai';
    public const PLATFORM_CLAUDE = 'claude';
    public const PLATFORM_GEMINI = 'gemini';
    public const PLATFORM_DEEPSEEK = 'deepseek';

    // Search/Social Platform constants
    public const PLATFORM_GOOGLE = 'google';
    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_FACEBOOK = 'facebook';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CitationCheck $check) {
            $check->uuid = Str::uuid();
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_cited' => 'boolean',
            'citations' => 'array',
            'metadata' => 'array',
            'progress_percent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function citationQuery(): BelongsTo
    {
        return $this->belongsTo(CitationQuery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CitationAlert::class);
    }

    /**
     * Check if the check is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if the check is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the check failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get the platform display name.
     */
    public function getPlatformNameAttribute(): string
    {
        return match ($this->platform) {
            self::PLATFORM_PERPLEXITY => 'Perplexity AI',
            self::PLATFORM_OPENAI => 'ChatGPT',
            self::PLATFORM_CLAUDE => 'Claude',
            self::PLATFORM_GEMINI => 'Google Gemini',
            self::PLATFORM_DEEPSEEK => 'DeepSeek',
            self::PLATFORM_GOOGLE => 'Google Search',
            self::PLATFORM_YOUTUBE => 'YouTube',
            self::PLATFORM_FACEBOOK => 'Facebook',
            default => ucfirst($this->platform),
        };
    }

    /**
     * Get the platform icon/color class.
     */
    public function getPlatformColorAttribute(): string
    {
        return match ($this->platform) {
            self::PLATFORM_PERPLEXITY => 'bg-blue-500',
            self::PLATFORM_OPENAI => 'bg-green-500',
            self::PLATFORM_CLAUDE => 'bg-orange-500',
            self::PLATFORM_GEMINI => 'bg-purple-500',
            self::PLATFORM_DEEPSEEK => 'bg-indigo-500',
            self::PLATFORM_GOOGLE => 'bg-red-500',
            self::PLATFORM_YOUTUBE => 'bg-red-600',
            self::PLATFORM_FACEBOOK => 'bg-blue-600',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get all available platforms.
     */
    public static function getAvailablePlatforms(): array
    {
        return [
            // AI Platforms
            self::PLATFORM_PERPLEXITY => 'Perplexity AI',
            self::PLATFORM_OPENAI => 'ChatGPT',
            self::PLATFORM_CLAUDE => 'Claude',
            self::PLATFORM_GEMINI => 'Google Gemini',
            self::PLATFORM_DEEPSEEK => 'DeepSeek',
            // Search/Social Platforms
            self::PLATFORM_GOOGLE => 'Google Search',
            self::PLATFORM_YOUTUBE => 'YouTube',
            self::PLATFORM_FACEBOOK => 'Facebook',
        ];
    }

    /**
     * Get AI platforms only.
     */
    public static function getAiPlatforms(): array
    {
        return [
            self::PLATFORM_PERPLEXITY,
            self::PLATFORM_OPENAI,
            self::PLATFORM_CLAUDE,
            self::PLATFORM_GEMINI,
            self::PLATFORM_DEEPSEEK,
        ];
    }

    /**
     * Get search/social platforms only.
     */
    public static function getSearchPlatforms(): array
    {
        return [
            self::PLATFORM_GOOGLE,
            self::PLATFORM_YOUTUBE,
            self::PLATFORM_FACEBOOK,
        ];
    }

    /**
     * Get citation details from the parsed response.
     */
    public function getCitationDetailsAttribute(): array
    {
        if (! $this->is_cited || ! $this->citations) {
            return [];
        }

        return $this->citations;
    }

    /**
     * Get the duration of the check in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}
