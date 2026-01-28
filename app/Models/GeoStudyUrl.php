<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoStudyUrl extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'geo_study_id',
        'url',
        'source_type',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function geoStudy(): BelongsTo
    {
        return $this->belongsTo(GeoStudy::class);
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function markQueued(): void
    {
        $this->update(['status' => self::STATUS_QUEUED]);
    }

    public function markProcessed(): void
    {
        $this->update(['status' => self::STATUS_PROCESSED]);
    }

    public function markSkipped(): void
    {
        $this->update(['status' => self::STATUS_SKIPPED]);
    }
}
