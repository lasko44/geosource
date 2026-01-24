<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GA4Connection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ga4_connections';

    protected $fillable = [
        'user_id',
        'team_id',
        'google_account_id',
        'property_id',
        'property_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'last_synced_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GA4Connection $connection) {
            $connection->uuid = Str::uuid();
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
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
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

    public function referralData(): HasMany
    {
        return $this->hasMany(GA4ReferralData::class, 'ga4_connection_id');
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        // Consider expired 5 minutes before actual expiry
        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Check if the connection needs token refresh.
     */
    public function needsTokenRefresh(): bool
    {
        return $this->is_active && $this->isTokenExpired();
    }

    /**
     * Update tokens after a refresh.
     */
    public function updateTokens(string $accessToken, ?string $refreshToken, int $expiresIn): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken ?? $this->refresh_token,
            'token_expires_at' => now()->addSeconds($expiresIn),
        ]);
    }

    /**
     * Mark the connection as synced.
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Deactivate the connection (e.g., on auth error).
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get AI referral data for the connection.
     */
    public function getAIReferralData(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $aiSources = config('citations.ai_referral_sources', []);

        return $this->referralData()
            ->where('date', '>=', now()->subDays($days))
            ->whereIn('source', $aiSources)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get AI traffic summary.
     */
    public function getAITrafficSummary(int $days = 30): array
    {
        $aiSources = config('citations.ai_referral_sources', []);

        $data = $this->referralData()
            ->where('date', '>=', now()->subDays($days))
            ->whereIn('source', $aiSources)
            ->selectRaw('source, SUM(sessions) as total_sessions, SUM(users) as total_users, SUM(pageviews) as total_pageviews')
            ->groupBy('source')
            ->get();

        $totalSessions = $data->sum('total_sessions');
        $totalUsers = $data->sum('total_users');
        $totalPageviews = $data->sum('total_pageviews');

        return [
            'total_sessions' => $totalSessions,
            'total_users' => $totalUsers,
            'total_pageviews' => $totalPageviews,
            'by_source' => $data->map(fn ($item) => [
                'source' => $item->source,
                'sessions' => $item->total_sessions,
                'users' => $item->total_users,
                'pageviews' => $item->total_pageviews,
                'percentage' => $totalSessions > 0
                    ? round(($item->total_sessions / $totalSessions) * 100, 1)
                    : 0,
            ])->toArray(),
        ];
    }
}
