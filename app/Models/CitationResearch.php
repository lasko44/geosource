<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Represents a citation research query that analyzes AI platform citations.
 */
class CitationResearch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'citation_research';

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    // Tier constants
    public const TIER_RESEARCH = 'research';

    public const TIER_RESEARCH_AUDIT = 'research_audit';

    // AI platforms to query
    public const PLATFORMS = [
        'perplexity',
        'openai',
        'claude',
        'gemini',
        'deepseek',
    ];

    protected $fillable = [
        'user_id',
        'team_id',
        'topic',
        'tier',
        'status',
        'progress_step',
        'progress_percent',
        'platforms_queried',
        'platform_responses',
        'sources_found',
        'analysis_results',
        'report',
        'error_message',
        'tokens_charged',
        'tokens_amount',
        'tokens_refunded',
        'started_at',
        'completed_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CitationResearch $research) {
            $research->uuid = Str::uuid();
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
            'platforms_queried' => 'array',
            'platform_responses' => 'array',
            'sources_found' => 'array',
            'analysis_results' => 'array',
            'tokens_charged' => 'boolean',
            'tokens_refunded' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the scans created as part of the audit tier.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Check if this research is using the audit tier.
     */
    public function isAuditTier(): bool
    {
        return $this->tier === self::TIER_RESEARCH_AUDIT;
    }

    /**
     * Check if the research is complete.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the research is in progress.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the research failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
