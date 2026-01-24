<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CitationQuery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'query',
        'domain',
        'brand',
        'is_active',
        'frequency',
        'last_checked_at',
        'next_check_at',
    ];

    // Frequency constants
    public const FREQUENCY_MANUAL = 'manual';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CitationQuery $query) {
            $query->uuid = Str::uuid();
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
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
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

    public function checks(): HasMany
    {
        return $this->hasMany(CitationCheck::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CitationAlert::class);
    }

    /**
     * Get the latest check for this query.
     */
    public function latestCheck(): ?CitationCheck
    {
        return $this->checks()->latest()->first();
    }

    /**
     * Get the latest check for a specific platform.
     */
    public function latestCheckForPlatform(string $platform): ?CitationCheck
    {
        return $this->checks()
            ->where('platform', $platform)
            ->where('status', 'completed')
            ->latest()
            ->first();
    }

    /**
     * Check if the query is due for a scheduled check.
     */
    public function isDueForCheck(): bool
    {
        if ($this->frequency === self::FREQUENCY_MANUAL) {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        if (! $this->next_check_at) {
            return true;
        }

        return $this->next_check_at->isPast();
    }

    /**
     * Calculate and set the next check time.
     */
    public function scheduleNextCheck(): void
    {
        $nextCheck = match ($this->frequency) {
            self::FREQUENCY_DAILY => now()->addDay(),
            self::FREQUENCY_WEEKLY => now()->addWeek(),
            default => null,
        };

        $this->update([
            'last_checked_at' => now(),
            'next_check_at' => $nextCheck,
        ]);
    }

    /**
     * Get citation status summary across all platforms.
     */
    public function getCitationSummaryAttribute(): array
    {
        $latestChecks = $this->checks()
            ->where('status', 'completed')
            ->selectRaw('platform, MAX(id) as latest_id')
            ->groupBy('platform')
            ->pluck('latest_id');

        $checks = CitationCheck::whereIn('id', $latestChecks)->get();

        return [
            'total_platforms_checked' => $checks->count(),
            'cited_on' => $checks->where('is_cited', true)->pluck('platform')->toArray(),
            'not_cited_on' => $checks->where('is_cited', false)->pluck('platform')->toArray(),
            'citation_rate' => $checks->count() > 0
                ? round(($checks->where('is_cited', true)->count() / $checks->count()) * 100, 1)
                : 0,
        ];
    }
}
