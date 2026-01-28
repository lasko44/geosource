<?php

namespace App\Nova\Metrics;

use App\Models\GeoStudyResult;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class GeoStudyPillarBreakdown extends Partition
{
    public $name = 'Weakest Pillars';

    public function calculate(NovaRequest $request): PartitionResult
    {
        // Get average scores for each pillar
        $pillars = [
            'Definitions' => 'pillar_definitions',
            'Structure' => 'pillar_structure',
            'Authority' => 'pillar_authority',
            'Machine Readable' => 'pillar_machine_readable',
            'Answerability' => 'pillar_answerability',
            'E-E-A-T' => 'pillar_eeat',
            'Citations' => 'pillar_citations',
            'AI Accessibility' => 'pillar_ai_accessibility',
            'Freshness' => 'pillar_freshness',
            'Readability' => 'pillar_readability',
            'Question Coverage' => 'pillar_question_coverage',
            'Multimedia' => 'pillar_multimedia',
        ];

        $averages = [];
        foreach ($pillars as $name => $column) {
            $avg = GeoStudyResult::where('status', 'completed')
                ->whereNotNull($column)
                ->avg($column);

            if ($avg !== null) {
                $averages[$name] = round($avg, 1);
            }
        }

        // Sort by score (lowest first - weakest pillars)
        asort($averages);

        // Take top 6 weakest pillars
        $weakest = array_slice($averages, 0, 6, true);

        return (new PartitionResult(collect($weakest)->toArray()))->colors([
            'Definitions' => '#3b82f6',
            'Structure' => '#8b5cf6',
            'Authority' => '#ec4899',
            'Machine Readable' => '#06b6d4',
            'Answerability' => '#10b981',
            'E-E-A-T' => '#f59e0b',
            'Citations' => '#ef4444',
            'AI Accessibility' => '#84cc16',
            'Freshness' => '#14b8a6',
            'Readability' => '#f97316',
            'Question Coverage' => '#6366f1',
            'Multimedia' => '#d946ef',
        ]);
    }

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'geo-study-pillar-breakdown';
    }
}
