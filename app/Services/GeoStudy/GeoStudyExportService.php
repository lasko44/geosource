<?php

namespace App\Services\GeoStudy;

use App\Models\GeoStudy;
use App\Models\GeoStudyResult;

class GeoStudyExportService
{
    /**
     * Export study results to CSV.
     */
    public function exportToCsv(GeoStudy $study): string
    {
        $results = $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->orderByDesc('total_score')
            ->get();

        $pillarNames = GeoStudyResult::pillarNames();

        // Build CSV
        $output = fopen('php://temp', 'r+');

        // Header row
        $headers = [
            'URL',
            'Title',
            'Domain',
            'Category',
            'Total Score',
            'Grade',
            'Percentage',
        ];

        // Add pillar headers
        foreach ($pillarNames as $name) {
            $headers[] = $name;
        }

        $headers[] = 'Processed At';

        fputcsv($output, $headers);

        // Data rows
        foreach ($results as $result) {
            $row = [
                $result->url,
                $result->title ?? '',
                $result->domain ?? '',
                $result->category ?? '',
                $result->total_score ?? '',
                $result->grade ?? '',
                $result->percentage ?? '',
            ];

            // Add pillar scores
            foreach (GeoStudyResult::pillarColumns() as $column) {
                $row[] = $result->$column ?? '';
            }

            $row[] = $result->processed_at?->toDateTimeString() ?? '';

            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export study to JSON.
     */
    public function exportToJson(GeoStudy $study): array
    {
        $results = $study->results()
            ->where('status', GeoStudyResult::STATUS_COMPLETED)
            ->orderByDesc('total_score')
            ->get();

        return [
            'study' => [
                'id' => $study->uuid,
                'name' => $study->name,
                'description' => $study->description,
                'category' => $study->category,
                'source_type' => $study->source_type,
                'total_urls' => $study->total_urls,
                'processed_urls' => $study->processed_urls,
                'failed_urls' => $study->failed_urls,
                'started_at' => $study->started_at?->toISOString(),
                'completed_at' => $study->completed_at?->toISOString(),
            ],
            'aggregate_stats' => $study->aggregate_stats,
            'category_breakdown' => $study->category_breakdown,
            'pillar_analysis' => $study->pillar_analysis,
            'top_performers' => $study->top_performers,
            'bottom_performers' => $study->bottom_performers,
            'results' => $results->map(function ($result) {
                return [
                    'id' => $result->uuid,
                    'url' => $result->url,
                    'title' => $result->title,
                    'domain' => $result->domain,
                    'category' => $result->category,
                    'total_score' => $result->total_score,
                    'grade' => $result->grade,
                    'percentage' => $result->percentage,
                    'pillar_scores' => $result->getPillarScores(),
                    'processed_at' => $result->processed_at?->toISOString(),
                ];
            })->toArray(),
            'exported_at' => now()->toISOString(),
        ];
    }

    /**
     * Export aggregate stats for blog embedding.
     */
    public function exportForBlog(GeoStudy $study): array
    {
        return [
            'study_name' => $study->name,
            'study_description' => $study->description,
            'total_urls_analyzed' => $study->total_urls,
            'completed_at' => $study->completed_at?->format('F j, Y'),
            'charts' => [
                'grade_distribution' => $this->formatGradeDistributionChart($study),
                'score_ranges' => $this->formatScoreRangesChart($study),
                'category_comparison' => $this->formatCategoryComparisonChart($study),
                'pillar_radar' => $this->formatPillarRadarChart($study),
            ],
            'highlights' => [
                'avg_score' => $study->aggregate_stats['avg_score'] ?? 0,
                'avg_percentage' => $study->aggregate_stats['avg_percentage'] ?? 0,
                'top_grade' => $this->getTopGrade($study),
                'weakest_pillar' => $this->getWeakestPillar($study),
                'strongest_pillar' => $this->getStrongestPillar($study),
            ],
            'top_3' => array_slice($study->top_performers ?? [], 0, 3),
            'bottom_3' => array_slice($study->bottom_performers ?? [], 0, 3),
        ];
    }

    /**
     * Format grade distribution for Chart.js pie chart.
     */
    private function formatGradeDistributionChart(GeoStudy $study): array
    {
        $distribution = $study->aggregate_stats['grade_distribution'] ?? [];

        return [
            'type' => 'pie',
            'labels' => array_keys($distribution),
            'data' => array_values($distribution),
            'colors' => [
                '#22c55e', // A+ - green
                '#4ade80', // A
                '#86efac', // A-
                '#3b82f6', // B+ - blue
                '#60a5fa', // B
                '#93c5fd', // B-
                '#f59e0b', // C+ - amber
                '#fbbf24', // C
                '#fcd34d', // C-
                '#f97316', // D+ - orange
                '#fb923c', // D
                '#ef4444', // F - red
            ],
        ];
    }

    /**
     * Format score ranges for Chart.js bar chart.
     */
    private function formatScoreRangesChart(GeoStudy $study): array
    {
        $ranges = $study->aggregate_stats['score_ranges'] ?? [];

        return [
            'type' => 'bar',
            'labels' => array_keys($ranges),
            'data' => array_values($ranges),
            'color' => '#3b82f6',
        ];
    }

    /**
     * Format category comparison for Chart.js grouped bar chart.
     */
    private function formatCategoryComparisonChart(GeoStudy $study): array
    {
        $breakdown = $study->category_breakdown ?? [];

        $labels = [];
        $avgScores = [];
        $counts = [];

        foreach ($breakdown as $category => $stats) {
            $labels[] = ucfirst($category);
            $avgScores[] = round($stats['avg_percentage'] ?? 0, 1);
            $counts[] = $stats['count'] ?? 0;
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Average Score %',
                    'data' => $avgScores,
                    'color' => '#3b82f6',
                ],
                [
                    'label' => 'URL Count',
                    'data' => $counts,
                    'color' => '#10b981',
                ],
            ],
        ];
    }

    /**
     * Format pillar analysis for Chart.js radar chart.
     */
    private function formatPillarRadarChart(GeoStudy $study): array
    {
        $analysis = $study->pillar_analysis ?? [];

        $labels = [];
        $data = [];

        foreach ($analysis as $pillar) {
            $labels[] = $pillar['name'] ?? '';
            $data[] = $pillar['avg_score'] ?? 0;
        }

        return [
            'type' => 'radar',
            'labels' => $labels,
            'data' => $data,
            'color' => '#8b5cf6',
        ];
    }

    /**
     * Get the most common top grade.
     */
    private function getTopGrade(GeoStudy $study): ?string
    {
        $distribution = $study->aggregate_stats['grade_distribution'] ?? [];

        foreach (['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F'] as $grade) {
            if (($distribution[$grade] ?? 0) > 0) {
                return $grade;
            }
        }

        return null;
    }

    /**
     * Get the weakest pillar name.
     */
    private function getWeakestPillar(GeoStudy $study): ?string
    {
        $analysis = $study->pillar_analysis ?? [];
        $first = reset($analysis);

        return $first['name'] ?? null;
    }

    /**
     * Get the strongest pillar name.
     */
    private function getStrongestPillar(GeoStudy $study): ?string
    {
        $analysis = $study->pillar_analysis ?? [];
        $last = end($analysis);

        return $last['name'] ?? null;
    }
}
