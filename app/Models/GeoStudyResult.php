<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GeoStudyResult extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'geo_study_id',
        'url',
        'title',
        'domain',
        'category',
        'total_score',
        'grade',
        'percentage',
        'pillar_definitions',
        'pillar_structure',
        'pillar_authority',
        'pillar_machine_readable',
        'pillar_answerability',
        'pillar_eeat',
        'pillar_citations',
        'pillar_ai_accessibility',
        'pillar_freshness',
        'pillar_readability',
        'pillar_question_coverage',
        'pillar_multimedia',
        'full_results',
        'status',
        'error_message',
        'source_type',
        'source_metadata',
        'processed_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GeoStudyResult $result) {
            $result->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'pillar_definitions' => 'decimal:2',
            'pillar_structure' => 'decimal:2',
            'pillar_authority' => 'decimal:2',
            'pillar_machine_readable' => 'decimal:2',
            'pillar_answerability' => 'decimal:2',
            'pillar_eeat' => 'decimal:2',
            'pillar_citations' => 'decimal:2',
            'pillar_ai_accessibility' => 'decimal:2',
            'pillar_freshness' => 'decimal:2',
            'pillar_readability' => 'decimal:2',
            'pillar_question_coverage' => 'decimal:2',
            'pillar_multimedia' => 'decimal:2',
            'full_results' => 'array',
            'source_metadata' => 'array',
            'processed_at' => 'datetime',
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

    // Pillar helpers
    public static function pillarColumns(): array
    {
        return [
            'pillar_definitions',
            'pillar_structure',
            'pillar_authority',
            'pillar_machine_readable',
            'pillar_answerability',
            'pillar_eeat',
            'pillar_citations',
            'pillar_ai_accessibility',
            'pillar_freshness',
            'pillar_readability',
            'pillar_question_coverage',
            'pillar_multimedia',
        ];
    }

    public static function pillarNames(): array
    {
        return [
            'pillar_definitions' => 'Definitions',
            'pillar_structure' => 'Structure',
            'pillar_authority' => 'Authority',
            'pillar_machine_readable' => 'Machine Readable',
            'pillar_answerability' => 'Answerability',
            'pillar_eeat' => 'E-E-A-T',
            'pillar_citations' => 'Citations',
            'pillar_ai_accessibility' => 'AI Accessibility',
            'pillar_freshness' => 'Freshness',
            'pillar_readability' => 'Readability',
            'pillar_question_coverage' => 'Question Coverage',
            'pillar_multimedia' => 'Multimedia',
        ];
    }

    public function getPillarScores(): array
    {
        $scores = [];
        foreach (self::pillarNames() as $column => $name) {
            $scores[$column] = [
                'name' => $name,
                'score' => $this->$column,
            ];
        }
        return $scores;
    }
}
