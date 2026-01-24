<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitationAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'citation_query_id',
        'citation_check_id',
        'type',
        'platform',
        'message',
        'is_read',
        'read_at',
    ];

    // Alert type constants
    public const TYPE_NEW_CITATION = 'new_citation';
    public const TYPE_LOST_CITATION = 'lost_citation';

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
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

    public function citationQuery(): BelongsTo
    {
        return $this->belongsTo(CitationQuery::class);
    }

    public function citationCheck(): BelongsTo
    {
        return $this->belongsTo(CitationCheck::class);
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Get the alert type display name.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_NEW_CITATION => 'New Citation',
            self::TYPE_LOST_CITATION => 'Lost Citation',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get the alert type color class.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_NEW_CITATION => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-950',
            self::TYPE_LOST_CITATION => 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-950',
            default => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-800',
        };
    }

    /**
     * Create an alert for a new citation.
     */
    public static function createNewCitationAlert(CitationCheck $check): self
    {
        $query = $check->citationQuery;

        return self::create([
            'user_id' => $check->user_id,
            'team_id' => $check->team_id,
            'citation_query_id' => $query->id,
            'citation_check_id' => $check->id,
            'type' => self::TYPE_NEW_CITATION,
            'platform' => $check->platform,
            'message' => "Your domain is now being cited on {$check->platform_name} for the query: \"{$query->query}\"",
        ]);
    }

    /**
     * Create an alert for a lost citation.
     */
    public static function createLostCitationAlert(CitationCheck $check): self
    {
        $query = $check->citationQuery;

        return self::create([
            'user_id' => $check->user_id,
            'team_id' => $check->team_id,
            'citation_query_id' => $query->id,
            'citation_check_id' => $check->id,
            'type' => self::TYPE_LOST_CITATION,
            'platform' => $check->platform,
            'message' => "Your domain is no longer being cited on {$check->platform_name} for the query: \"{$query->query}\"",
        ]);
    }
}
