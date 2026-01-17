<?php

namespace App\Services\GEO;

/**
 * GEO Score Data Transfer Object
 *
 * Represents a complete GEO score result with typed access.
 */
class GeoScore
{
    public function __construct(
        public readonly float $score,
        public readonly float $maxScore,
        public readonly float $percentage,
        public readonly string $grade,
        public readonly array $pillars,
        public readonly array $recommendations,
        public readonly array $summary,
        public readonly string $scoredAt,
    ) {}

    /**
     * Create from raw scorer output.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            score: $data['score'],
            maxScore: $data['max_score'],
            percentage: $data['percentage'],
            grade: $data['grade'],
            pillars: $data['pillars'],
            recommendations: $data['recommendations'],
            summary: $data['summary'],
            scoredAt: $data['scored_at'],
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'max_score' => $this->maxScore,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'pillars' => $this->pillars,
            'recommendations' => $this->recommendations,
            'summary' => $this->summary,
            'scored_at' => $this->scoredAt,
        ];
    }

    /**
     * Get a specific pillar's data.
     */
    public function getPillar(string $key): ?array
    {
        return $this->pillars[$key] ?? null;
    }

    /**
     * Check if score is passing (>= 60%).
     */
    public function isPassing(): bool
    {
        return $this->percentage >= 60;
    }

    /**
     * Check if score is excellent (>= 85%).
     */
    public function isExcellent(): bool
    {
        return $this->percentage >= 85;
    }

    /**
     * Get high-priority recommendations.
     */
    public function getHighPriorityRecommendations(): array
    {
        return array_filter(
            $this->recommendations,
            fn ($rec) => $rec['priority'] === 'high'
        );
    }

    /**
     * Get the weakest pillar.
     */
    public function getWeakestPillar(): ?string
    {
        return $this->summary['focus_area'] ?? null;
    }
}
