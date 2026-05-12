<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Represents a website GEO scan with scoring and recommendations.
 */
class Scan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Maximum number of competitor scans allowed per team.
     */
    public const MAX_COMPETITORS_PER_TEAM = 10;

    protected $fillable = [
        'user_id',
        'team_id',
        'scheduled_scan_id',
        'citation_research_id',
        'url',
        'title',
        'score',
        'grade',
        'results',
        'status',
        'progress_step',
        'progress_percent',
        'error_message',
        'internal_error',
        'started_at',
        'completed_at',
        'requested_tier',
        'is_competitor',
        'auto_find_competitors',
        'competitor_scan_tier',
        'competitor_discovery_status',
        'competitors_found',
        'parent_scan_id',
        'tokens_charged',
        'tokens_amount',
        'tokens_refunded',
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

    /**
     * Resolve the model for route model binding.
     * Falls back to ID lookup for old scans without UUIDs.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        // Try UUID first
        $scan = $this->where('uuid', $value)->first();

        // If not found and value is numeric, try ID as fallback for old scans
        if (! $scan && is_numeric($value)) {
            $scan = $this->where('id', $value)->first();
        }

        return $scan;
    }

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_competitor' => 'boolean',
            'auto_find_competitors' => 'boolean',
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
     * Get the scheduled scan that created this scan.
     */
    public function scheduledScan(): BelongsTo
    {
        return $this->belongsTo(ScheduledScan::class);
    }

    /**
     * Get the citation research that created this scan.
     */
    public function citationResearch(): BelongsTo
    {
        return $this->belongsTo(CitationResearch::class);
    }

    /**
     * Get the parent scan that triggered this competitor discovery.
     */
    public function parentScan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_scan_id');
    }

    /**
     * Get competitor scans discovered from this scan.
     */
    public function discoveredCompetitors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_scan_id');
    }

    /**
     * Scope to filter competitor scans.
     */
    public function scopeCompetitors(Builder $query): Builder
    {
        return $query->where('is_competitor', true);
    }

    /**
     * Scope to filter own content scans (non-competitors).
     */
    public function scopeOwnContent(Builder $query): Builder
    {
        return $query->where('is_competitor', false);
    }

    /**
     * Get competitor count for a team.
     */
    public static function competitorCountForTeam(int $teamId): int
    {
        return self::where('team_id', $teamId)
            ->where('is_competitor', true)
            ->count();
    }

    /**
     * Get competitor count for a user (personal scans).
     */
    public static function competitorCountForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->whereNull('team_id')
            ->where('is_competitor', true)
            ->count();
    }

    /**
     * Check if team/user can add more competitors.
     *
     * Note: No limit is enforced since any URL can be scanned by anyone.
     */
    public static function canAddCompetitor(?int $teamId, ?int $userId): bool
    {
        return $teamId !== null || $userId !== null;
    }

    /**
     * Get remaining competitor slots.
     *
     * Note: Returns unlimited since no limit is enforced.
     */
    public static function remainingCompetitorSlots(?int $teamId, ?int $userId): int
    {
        return PHP_INT_MAX;
    }

    /**
     * Get the score as a percentage.
     */
    public function getScorePercentageAttribute(): float
    {
        $maxScore = Arr::get($this->results, 'max_score', 100);

        return round(($this->score / $maxScore) * 100, 1);
    }

    /**
     * Get the grade color class.
     */
    public function getGradeColorAttribute(): string
    {
        return match (true) {
            str_starts_with($this->grade ?? '', 'A') => 'text-green-600',
            str_starts_with($this->grade ?? '', 'B') => 'text-blue-600',
            str_starts_with($this->grade ?? '', 'C') => 'text-yellow-600',
            str_starts_with($this->grade ?? '', 'D') => 'text-orange-600',
            default => 'text-grey-600',
        };
    }

    /**
     * Get pillar scores.
     */
    public function getPillarScoresAttribute(): array
    {
        return Arr::get($this->results, 'pillars', []);
    }

    /**
     * Get recommendations.
     */
    public function getRecommendationsAttribute(): array
    {
        return Arr::get($this->results, 'recommendations', []);
    }
}
