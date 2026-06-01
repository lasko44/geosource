<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks entries in the GEO effectiveness study correlating scores with AI citations.
 */
class GeoStudyEntry extends Model
{
    protected $fillable = [
        'study_version',
        'url',
        'domain',
        'industry',
        'site_size',
        'query',
        'scan_id',
        'geo_score',
        'geo_grade',
        'pillar_scores',
        'citation_query_id',
        'citations_checked',
        'citations_cited',
        'citation_rate',
        'platforms_cited',
        'status',
        'error_message',
    ];

    protected $casts = [
        'pillar_scores' => 'array',
        'platforms_cited' => 'array',
        'citation_rate' => 'decimal:2',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function citationQuery(): BelongsTo
    {
        return $this->belongsTo(CitationQuery::class);
    }

    public function scopePending($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeScanned($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'scanned');
    }

    public function scopeCompleted($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'completed');
    }
}
