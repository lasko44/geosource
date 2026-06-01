<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores per-industry GEO scoring benchmarks derived from correlation data.
 */
class IndustryBenchmark extends Model
{
    protected $fillable = [
        'industry',
        'sample_size',
        'avg_geo_score',
        'avg_citation_rate',
        'avg_citation_readiness',
        'dominant_content_type',
        'p25_score',
        'p50_score',
        'p75_score',
        'pillar_averages',
        'data_source',
    ];

    protected $casts = [
        'pillar_averages' => 'array',
        'avg_geo_score' => 'decimal:1',
        'avg_citation_rate' => 'decimal:2',
        'avg_citation_readiness' => 'decimal:1',
        'p25_score' => 'decimal:1',
        'p50_score' => 'decimal:1',
        'p75_score' => 'decimal:1',
    ];

    /**
     * Get the percentile rank for a given score in this industry.
     */
    public function getPercentileRank(float $score): string
    {
        if ($this->p75_score && $score >= $this->p75_score) {
            return 'top 25%';
        }
        if ($this->p50_score && $score >= $this->p50_score) {
            return 'top 50%';
        }
        if ($this->p25_score && $score >= $this->p25_score) {
            return 'top 75%';
        }

        return 'bottom 25%';
    }
}
