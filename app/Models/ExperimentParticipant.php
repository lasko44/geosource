<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records a visitor's assignment to an experiment variant and conversion status.
 */
class ExperimentParticipant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'experiment_id',
        'visitor_id',
        'user_id',
        'variant',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter participants who converted.
     */
    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereNotNull('converted_at');
    }
}
