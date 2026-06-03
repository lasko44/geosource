<?php

namespace App\Services\GEO;

use Illuminate\Support\Arr;

/**
 * Classifies a page's content into ecommerce / DTC product categories using a
 * keyword taxonomy from config/category-fit.php. Outputs the top-N categories
 * with confidence scores so the scan UI can surface category fit and flag
 * mismatches when a target query targets a category the page doesn't compete in.
 *
 * Keyword-based v1; future iterations can swap in embedding-based or LLM
 * classification without changing the public interface.
 */
class CategoryFitService
{
    private const TOP_N = 3;
    private const MIN_CONFIDENCE = 1.5;

    /**
     * Classify content into categories. Returns top-N categories ordered by
     * confidence with per-category match details.
     *
     * @return array{matches: array<int, array{category: string, label: string, confidence: float, matched_terms: array<int, string>}>, primary: ?string}
     */
    public function classify(string $content): array
    {
        $taxonomy = config('category-fit', []);
        $normalized = strtolower(strip_tags($content));

        $scores = [];
        foreach ($taxonomy as $key => $cat) {
            $score = 0.0;
            $matchedTerms = [];

            foreach (Arr::get($cat, 'core', []) as $term) {
                $count = $this->countOccurrences($normalized, $term);
                if ($count > 0) {
                    $score += 3.0 * $count;
                    $matchedTerms[] = $term;
                }
            }
            foreach (Arr::get($cat, 'supporting', []) as $term) {
                $count = $this->countOccurrences($normalized, $term);
                if ($count > 0) {
                    $score += 1.5 * $count;
                    $matchedTerms[] = $term;
                }
            }
            foreach (Arr::get($cat, 'generic', []) as $term) {
                $count = $this->countOccurrences($normalized, $term);
                if ($count > 0) {
                    $score += 0.5 * $count;
                    $matchedTerms[] = $term;
                }
            }

            if ($score >= self::MIN_CONFIDENCE) {
                $scores[] = [
                    'category' => $key,
                    'label' => Arr::get($cat, 'label', $key),
                    'confidence' => round($score, 1),
                    'matched_terms' => array_values(array_unique($matchedTerms)),
                ];
            }
        }

        usort($scores, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
        $matches = array_slice($scores, 0, self::TOP_N);

        return [
            'matches' => $matches,
            'primary' => $matches[0]['category'] ?? null,
        ];
    }

    /**
     * Check whether a given query targets a category the page is in. Used by
     * the scan UI to flag mismatch warnings (e.g. "running shoes" query
     * against an Allbirds homepage which the classifier now reads as
     * lifestyle apparel, not running).
     */
    public function queryMatchesPage(string $query, array $classification): bool
    {
        $primary = $classification['primary'] ?? null;
        if (! $primary) {
            return true; // can't classify, don't flag
        }

        $taxonomy = config('category-fit', []);
        $cat = $taxonomy[$primary] ?? null;
        if (! $cat) {
            return true;
        }

        $queryNorm = strtolower($query);
        foreach (array_merge($cat['core'] ?? [], $cat['supporting'] ?? [], $cat['generic'] ?? []) as $term) {
            if (str_contains($queryNorm, strtolower($term))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Count word-boundary-aware occurrences of $term in $haystack.
     * Generic single-word terms still get word boundaries to avoid
     * "shoe" matching inside "ashore".
     */
    private function countOccurrences(string $haystack, string $term): int
    {
        $pattern = '/\b'.preg_quote($term, '/').'\b/iu';
        $matches = [];
        return preg_match_all($pattern, $haystack, $matches) ?: 0;
    }
}
