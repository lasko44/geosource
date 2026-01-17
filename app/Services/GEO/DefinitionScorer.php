<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on clear definitions.
 *
 * Measures:
 * - Definition phrases exist ("is a", "refers to", "means", etc.)
 * - Definitions appear early in content
 * - Entity is explicitly mentioned in definition
 */
class DefinitionScorer implements ScorerInterface
{
    private const MAX_SCORE = 20;

    private const DEFINITION_PATTERNS = [
        '/\bis\s+(?:a|an|the)\b/i',
        '/\brefers?\s+to\b/i',
        '/\bmeans?\b/i',
        '/\bis\s+defined\s+as\b/i',
        '/\bcan\s+be\s+described\s+as\b/i',
        '/\bis\s+known\s+as\b/i',
        '/\bis\s+called\b/i',
        '/\brepresents?\b/i',
        '/\bdescribes?\b/i',
    ];

    public function score(string $content, array $context = []): array
    {
        $plainText = $this->stripHtml($content);
        $sentences = $this->getSentences($plainText);
        $entity = $context['entity'] ?? $this->extractEntity($plainText);

        $details = [
            'definitions_found' => [],
            'early_definition' => false,
            'entity_in_definition' => false,
            'entity' => $entity,
        ];

        $definitionScore = 0;
        $earlyDefinitionScore = 0;
        $entityMentionScore = 0;

        // Check for definition patterns
        $definitionCount = 0;
        foreach ($sentences as $index => $sentence) {
            foreach (self::DEFINITION_PATTERNS as $pattern) {
                if (preg_match($pattern, $sentence)) {
                    $definitionCount++;
                    $details['definitions_found'][] = [
                        'sentence' => $this->truncate($sentence, 150),
                        'position' => $index + 1,
                        'pattern' => $pattern,
                    ];

                    // Check if definition appears early (first 20% of content)
                    if ($index < count($sentences) * 0.2) {
                        $details['early_definition'] = true;
                    }

                    // Check if entity is mentioned in definition
                    if ($entity && stripos($sentence, $entity) !== false) {
                        $details['entity_in_definition'] = true;
                    }

                    break;
                }
            }
        }

        // Score calculation
        // Up to 8 points for having definitions (diminishing returns)
        $definitionScore = min(8, $definitionCount * 2);

        // Up to 6 points for early definition
        if ($details['early_definition']) {
            $earlyDefinitionScore = 6;
        }

        // Up to 6 points for entity in definition
        if ($details['entity_in_definition']) {
            $entityMentionScore = 6;
        }

        $totalScore = $definitionScore + $earlyDefinitionScore + $entityMentionScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'definition_phrases' => $definitionScore,
                    'early_definition' => $earlyDefinitionScore,
                    'entity_mention' => $entityMentionScore,
                ],
            ]),
        ];
    }

    public function getMaxScore(): float
    {
        return self::MAX_SCORE;
    }

    public function getName(): string
    {
        return 'Clear Definitions';
    }

    private function stripHtml(string $content): string
    {
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

        return trim(strip_tags($content));
    }

    private function getSentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, fn ($s) => strlen(trim($s)) > 10);
    }

    private function extractEntity(string $text): ?string
    {
        // Extract first significant noun phrase (simplified)
        $firstSentence = $this->getSentences($text)[0] ?? '';
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z]?[a-z]+){0,3})/', $firstSentence, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }
}
