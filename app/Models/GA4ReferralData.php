<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GA4ReferralData extends Model
{
    use HasFactory;

    protected $table = 'ga4_referral_data';

    protected $fillable = [
        'ga4_connection_id',
        'team_id',
        'date',
        'source',
        'medium',
        'sessions',
        'users',
        'pageviews',
        'engaged_sessions',
        'bounce_rate',
        'avg_session_duration',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sessions' => 'integer',
            'users' => 'integer',
            'pageviews' => 'integer',
            'engaged_sessions' => 'integer',
            'bounce_rate' => 'float',
            'avg_session_duration' => 'float',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GA4Connection::class, 'ga4_connection_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if this is from an AI platform.
     */
    public function isAISource(): bool
    {
        $aiSources = config('citations.ai_referral_sources', []);

        return in_array($this->source, $aiSources);
    }

    /**
     * Get engagement rate.
     */
    public function getEngagementRateAttribute(): float
    {
        if ($this->sessions === 0) {
            return 0;
        }

        return round(($this->engaged_sessions / $this->sessions) * 100, 1);
    }

    /**
     * Get average session duration formatted.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->avg_session_duration) {
            return '0:00';
        }

        $minutes = floor($this->avg_session_duration / 60);
        $seconds = $this->avg_session_duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Upsert referral data (update or create).
     */
    public static function upsertReferralData(
        int $connectionId,
        ?int $teamId,
        string $date,
        string $source,
        array $metrics
    ): self {
        return self::updateOrCreate(
            [
                'ga4_connection_id' => $connectionId,
                'date' => $date,
                'source' => $source,
            ],
            array_merge([
                'team_id' => $teamId,
            ], $metrics)
        );
    }
}
