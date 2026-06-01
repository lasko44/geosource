<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links scan GEO scores to citation outcomes for the same domain,
 * enabling continuous algorithm improvement from real-world data.
 */
class GeoCorrelation extends Model
{
    protected $fillable = [
        'domain',
        'url',
        'scan_id',
        'geo_score',
        'geo_percentage',
        'geo_grade',
        'citation_readiness_score',
        'content_type',
        'pillar_scores',
        'citation_query_id',
        'query',
        'platforms_checked',
        'platforms_cited',
        'citation_rate',
        'platforms_cited_list',
        'user_id',
        'visitor_id',
        'industry',
        'source',
    ];

    protected $casts = [
        'pillar_scores' => 'array',
        'platforms_cited_list' => 'array',
        'citation_rate' => 'decimal:2',
        'geo_percentage' => 'decimal:1',
        'citation_readiness_score' => 'decimal:1',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function citationQuery(): BelongsTo
    {
        return $this->belongsTo(CitationQuery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithBothDataPoints($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('geo_score')->whereNotNull('citation_rate');
    }

    public function scopeForIndustry($query, string $industry): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('industry', $industry);
    }
}
