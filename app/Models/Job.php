<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'jobs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available_at' => 'datetime',
        'created_at' => 'datetime',
        'reserved_at' => 'datetime',
    ];

    /**
     * Get the decoded payload.
     */
    public function getDecodedPayloadAttribute(): array
    {
        return json_decode($this->payload ?? '{}', true);
    }

    /**
     * Get the job display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->decoded_payload['displayName'] ?? 'Unknown';
    }

    /**
     * Get the short job name (class name only).
     */
    public function getShortNameAttribute(): string
    {
        $displayName = $this->display_name;

        return class_basename($displayName);
    }
}
