<?php

namespace App\Services\GeoStudy;

use App\Models\GeoStudy;
use App\Models\GeoStudyResult;
use Illuminate\Support\Facades\DB;

class GeoStudyAggregatorService
{
    /**
     * Aggregate all results for a study.
     */
    public function aggregate(GeoStudy $study): array
    {
        $results = $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->get();

        if ($results->isEmpty()) {
            return [
                'stats' => $this->getEmptyStats(),
                'category_breakdown' => [],
                'pillar_analysis' => [],
                'top_performers' => [],
                'bottom_performers' => [],
            ];
        }

        return [
            'stats' => $this->calculateStats($results),
            'category_breakdown' => $this->calculateCategoryBreakdown($study),
            'pillar_analysis' => $this->calculatePillarAnalysis($study),
            'top_performers' => $this->getTopPerformers($study, 10),
            'bottom_performers' => $this->getBottomPerformers($study, 10),
        ];
    }

    /**
     * Calculate overall statistics.
     */
    private function calculateStats($results): array
    {
        $count = $results->count();
        $scores = $results->pluck('total_score')->filter();
        $percentages = $results->pluck('percentage')->filter();

        return [
            'total_urls' => $count,
            'avg_score' => round($scores->avg(), 2),
            'median_score' => $this->median($scores->toArray()),
            'min_score' => round($scores->min(), 2),
            'max_score' => round($scores->max(), 2),
            'std_dev' => round($this->standardDeviation($scores->toArray()), 2),
            'avg_percentage' => round($percentages->avg(), 2),
            'grade_distribution' => $this->calculateGradeDistribution($results),
            'score_ranges' => $this->calculateScoreRanges($results),
        ];
    }

    /**
     * Calculate grade distribution.
     */
    private function calculateGradeDistribution($results): array
    {
        $grades = $results->pluck('grade')->countBy();

        $gradeOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F'];

        $distribution = [];
        foreach ($gradeOrder as $grade) {
            $distribution[$grade] = $grades->get($grade, 0);
        }

        return $distribution;
    }

    /**
     * Calculate score ranges for histogram.
     */
    private function calculateScoreRanges($results): array
    {
        $ranges = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($results as $result) {
            $pct = $result->percentage ?? 0;

            if ($pct <= 20) {
                $ranges['0-20']++;
            } elseif ($pct <= 40) {
                $ranges['21-40']++;
            } elseif ($pct <= 60) {
                $ranges['41-60']++;
            } elseif ($pct <= 80) {
                $ranges['61-80']++;
            } else {
                $ranges['81-100']++;
            }
        }

        return $ranges;
    }

    /**
     * Calculate breakdown by category.
     */
    private function calculateCategoryBreakdown(GeoStudy $study): array
    {
        return $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->whereNotNull('category')
            ->select(
                'category',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(total_score) as avg_score'),
                DB::raw('AVG(percentage) as avg_percentage'),
                DB::raw('MIN(total_score) as min_score'),
                DB::raw('MAX(total_score) as max_score')
            )
            ->groupBy('category')
            ->get()
            ->keyBy('category')
            ->toArray();
    }

    /**
     * Analyze pillar performance.
     */
    private function calculatePillarAnalysis(GeoStudy $study): array
    {
        $pillarColumns = GeoStudyResult::pillarColumns();
        $pillarNames = GeoStudyResult::pillarNames();

        $analysis = [];

        foreach ($pillarColumns as $column) {
            $stats = $study->results()
                ->where('status', GeoStudyResult::STATUS_COMPLETED)
                ->whereNotNull($column)
                ->select(
                    DB::raw("AVG({$column}) as avg_score"),
                    DB::raw("MIN({$column}) as min_score"),
                    DB::raw("MAX({$column}) as max_score"),
                    DB::raw("STDDEV({$column}) as std_dev"),
                    DB::raw("COUNT(*) as count")
                )
                ->first();

            $analysis[$column] = [
                'name' => $pillarNames[$column] ?? $column,
                'avg_score' => round($stats->avg_score ?? 0, 2),
                'min_score' => round($stats->min_score ?? 0, 2),
                'max_score' => round($stats->max_score ?? 0, 2),
                'std_dev' => round($stats->std_dev ?? 0, 2),
                'count' => $stats->count ?? 0,
            ];
        }

        // Sort by average score to identify weakest pillars
        uasort($analysis, fn ($a, $b) => $a['avg_score'] <=> $b['avg_score']);

        return $analysis;
    }

    /**
     * Get top performing URLs.
     */
    private function getTopPerformers(GeoStudy $study, int $limit = 10): array
    {
        return $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->orderByDesc('total_score')
            ->limit($limit)
            ->get(['uuid', 'url', 'title', 'domain', 'total_score', 'grade', 'percentage'])
            ->toArray();
    }

    /**
     * Get bottom performing URLs.
     */
    private function getBottomPerformers(GeoStudy $study, int $limit = 10): array
    {
        return $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->orderBy('total_score')
            ->limit($limit)
            ->get(['uuid', 'url', 'title', 'domain', 'total_score', 'grade', 'percentage'])
            ->toArray();
    }

    /**
     * Get empty stats structure.
     */
    private function getEmptyStats(): array
    {
        return [
            'total_urls' => 0,
            'avg_score' => 0,
            'median_score' => 0,
            'min_score' => 0,
            'max_score' => 0,
            'std_dev' => 0,
            'avg_percentage' => 0,
            'grade_distribution' => [],
            'score_ranges' => [],
        ];
    }

    /**
     * Calculate median of an array.
     */
    private function median(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return round(($values[$middle - 1] + $values[$middle]) / 2, 2);
        }

        return round($values[$middle], 2);
    }

    /**
     * Calculate standard deviation.
     */
    private function standardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $values)) / (count($values) - 1);

        return sqrt($variance);
    }
}
