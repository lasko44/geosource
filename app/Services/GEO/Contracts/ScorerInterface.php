<?php

namespace App\Services\GEO\Contracts;

interface ScorerInterface
{
    /**
     * Calculate the score for the given content.
     *
     * @param  string  $content  The HTML or text content to analyze
     * @param  array  $context  Additional context (url, team_id, etc.)
     * @return array{score: float, max_score: float, details: array}
     */
    public function score(string $content, array $context = []): array;

    /**
     * Get the maximum possible score for this pillar.
     */
    public function getMaxScore(): float;

    /**
     * Get the pillar name.
     */
    public function getName(): string;
}
