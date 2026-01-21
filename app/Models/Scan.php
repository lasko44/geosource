<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'url',
        'title',
        'score',
        'grade',
        'results',
        'status',
        'progress_step',
        'progress_percent',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Scan $scan) {
            $scan->uuid = Str::uuid();
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
            'score' => 'float',
            'results' => 'array',
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
     * Get the score as a percentage.
     */
    public function getScorePercentageAttribute(): float
    {
        $maxScore = $this->results['max_score'] ?? 100;

        return round(($this->score / $maxScore) * 100, 1);
    }

    /**
     * Get the grade color class.
     */
    public function getGradeColorAttribute(): string
    {
        return match (true) {
            str_starts_with($this->grade, 'A') => 'text-green-600',
            str_starts_with($this->grade, 'B') => 'text-blue-600',
            str_starts_with($this->grade, 'C') => 'text-yellow-600',
            str_starts_with($this->grade, 'D') => 'text-orange-600',
            default => 'text-red-600',
        };
    }

    /**
     * Get pillar scores.
     */
    public function getPillarScoresAttribute(): array
    {
        return $this->results['pillars'] ?? [];
    }

    /**
     * Get recommendations.
     */
    public function getRecommendationsAttribute(): array
    {
        return $this->results['recommendations'] ?? [];
    }
}
