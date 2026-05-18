<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an A/B experiment with multi-armed bandit variant assignment.
 */
class Experiment extends Model
{
    protected $fillable = [
        'name',
        'description',
        'variants',
        'status',
        'started_at',
        'ended_at',
        'winning_variant',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExperimentParticipant::class);
    }

    /**
     * Scope to filter experiments that are currently running.
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }
}
