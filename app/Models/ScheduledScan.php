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
     * Calculate the next run time based on frequency.
     */
    public function calculateNextRunAt(): \Carbon\Carbon
    {
        $now = now();
        $time = $this->scheduled_time ? \Carbon\Carbon::parse($this->scheduled_time) : now()->setTime(9, 0);

        $next = $now->copy()->setTimeFrom($time);

        // If the time has already passed today, start from tomorrow
        if ($next->lte($now)) {
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
                if ($next->lte($now)) {
                    $next->addMonth()->day($dayOfMonth);
                }
                break;
        }

        return $next;
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

        return match ($this->frequency) {
            'daily' => "Daily at {$time}",
            'weekly' => 'Every '.['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$this->day_of_week ?? 1]." at {$time}",
            'monthly' => "Monthly on day ".($this->day_of_month ?? 1)." at {$time}",
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
