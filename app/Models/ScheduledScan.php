<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ScheduledScan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'url',
        'name',
        'frequency',
        'scheduled_time',
        'day_of_week',
        'day_of_month',
        'is_active',
        'last_run_at',
        'next_run_at',
        'total_runs',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'scheduled_time' => 'datetime:H:i',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ScheduledScan $scheduledScan) {
            $scheduledScan->uuid = Str::uuid();
            $scheduledScan->next_run_at = $scheduledScan->calculateNextRunAt();
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
     * Get the user that owns the scheduled scan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the scheduled scan.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get all scans created by this scheduled scan.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class, 'scheduled_scan_id');
    }

    /**
     * Get the user's timezone, falling back to UTC.
     */
    public function getUserTimezone(): string
    {
        // Load user if not already loaded
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user?->timezone ?? 'UTC';
    }

    /**
     * Calculate the next run time based on frequency.
     * Times are interpreted in the user's timezone and stored in UTC.
     */
    public function calculateNextRunAt(): \Carbon\Carbon
    {
        $userTimezone = $this->getUserTimezone();

        // Get current time in the user's timezone
        $nowInUserTz = \Carbon\Carbon::now($userTimezone);

        // Parse the scheduled time in the user's timezone
        $scheduledHour = 9;
        $scheduledMinute = 0;

        if ($this->scheduled_time) {
            $timeParts = \Carbon\Carbon::parse($this->scheduled_time);
            $scheduledHour = $timeParts->hour;
            $scheduledMinute = $timeParts->minute;
        }

        // Create the next run time in the user's timezone
        $next = $nowInUserTz->copy()->setTime($scheduledHour, $scheduledMinute, 0);

        // If the time has already passed today, start from tomorrow
        if ($next->lte($nowInUserTz)) {
            $next->addDay();
        }

        switch ($this->frequency) {
            case 'daily':
                // Already set to next occurrence
                break;

            case 'weekly':
                $dayOfWeek = $this->day_of_week ?? 1; // Default to Monday
                while ($next->dayOfWeek !== $dayOfWeek) {
                    $next->addDay();
                }
                break;

            case 'monthly':
                $dayOfMonth = min($this->day_of_month ?? 1, 28); // Cap at 28 to avoid month issues
                $next->day($dayOfMonth);
                if ($next->lte($nowInUserTz)) {
                    $next->addMonth()->day($dayOfMonth);
                }
                break;
        }

        // Convert to UTC for storage (Laravel will handle this automatically,
        // but we're explicit here for clarity)
        return $next->setTimezone('UTC');
    }

    /**
     * Mark as run and calculate next run time.
     */
    public function markAsRun(): void
    {
        $this->last_run_at = now();
        $this->total_runs++;
        $this->next_run_at = $this->calculateNextRunAt();
        $this->save();
    }

    /**
     * Get a human-readable schedule description.
     */
    public function getScheduleDescriptionAttribute(): string
    {
        $time = $this->scheduled_time ? \Carbon\Carbon::parse($this->scheduled_time)->format('g:i A') : '9:00 AM';
        $timezone = $this->getUserTimezone();
        $tzAbbrev = \Carbon\Carbon::now($timezone)->format('T');

        return match ($this->frequency) {
            'daily' => "Daily at {$time} {$tzAbbrev}",
            'weekly' => 'Every '.['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$this->day_of_week ?? 1]." at {$time} {$tzAbbrev}",
            'monthly' => "Monthly on day ".($this->day_of_month ?? 1)." at {$time} {$tzAbbrev}",
            default => 'Unknown schedule',
        };
    }

    /**
     * Scope for active scheduled scans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for scheduled scans due to run.
     */
    public function scopeDue($query)
    {
        return $query->active()->where('next_run_at', '<=', now());
    }
}
