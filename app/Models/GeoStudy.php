<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GeoStudy extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COLLECTING = 'collecting';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const CATEGORY_NEWS = 'news';
    public const CATEGORY_ECOMMERCE = 'ecommerce';
    public const CATEGORY_SAAS = 'saas';
    public const CATEGORY_BLOG = 'blog';
    public const CATEGORY_MIXED = 'mixed';

    public const SOURCE_CSV = 'csv';
    public const SOURCE_SERP = 'serp';
    public const SOURCE_SITEMAP = 'sitemap';

    protected $fillable = [
        'name',
        'description',
        'category',
        'source_type',
        'source_config',
        'status',
        'total_urls',
        'processed_urls',
        'failed_urls',
        'progress_percent',
        'batch_id',
        'aggregate_stats',
        'category_breakdown',
        'pillar_analysis',
        'top_performers',
        'bottom_performers',
        'created_by',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GeoStudy $study) {
            $study->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'source_config' => 'array',
            'aggregate_stats' => 'array',
            'category_breakdown' => 'array',
            'pillar_analysis' => 'array',
            'top_performers' => 'array',
            'bottom_performers' => 'array',
            'total_urls' => 'integer',
            'processed_urls' => 'integer',
            'failed_urls' => 'integer',
            'progress_percent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(GeoStudyResult::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(GeoStudyUrl::class);
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCollecting(): bool
    {
        return $this->status === self::STATUS_COLLECTING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_COLLECTING, self::STATUS_PROCESSING]);
    }

    public function canStart(): bool
    {
        return $this->isDraft() && $this->urls()->count() > 0;
    }

    public function canCancel(): bool
    {
        return $this->isInProgress();
    }

    // Progress helpers
    public function updateProgress(): void
    {
        $total = $this->total_urls;
        $processed = $this->results()->whereIn('status', ['completed', 'failed'])->count();
        $failed = $this->results()->where('status', 'failed')->count();

        $this->update([
            'processed_urls' => $processed,
            'failed_urls' => $failed,
            'progress_percent' => $total > 0 ? (int) round(($processed / $total) * 100) : 0,
        ]);
    }

    // Category options
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_NEWS => 'News',
            self::CATEGORY_ECOMMERCE => 'E-commerce',
            self::CATEGORY_SAAS => 'SaaS',
            self::CATEGORY_BLOG => 'Blog',
            self::CATEGORY_MIXED => 'Mixed',
        ];
    }

    // Source type options
    public static function sourceTypeOptions(): array
    {
        return [
            self::SOURCE_CSV => 'CSV Import',
            self::SOURCE_SERP => 'SERP API',
            self::SOURCE_SITEMAP => 'Sitemap',
        ];
    }

    // Status options
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_COLLECTING => 'Collecting',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
