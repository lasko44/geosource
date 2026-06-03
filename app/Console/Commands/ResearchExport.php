<?php

namespace App\Console\Commands;

use App\Models\CitationCheck;
use App\Models\GeoStudyEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

/**
 * One-time maintenance command. Reads the local research data out of the
 * study tables and writes JSON snapshots to database/research-data/.
 *
 * Used once when each study was first run. Re-run only if you re-do a study
 * and want to refresh its snapshot. Day-to-day deployment doesn't need this.
 */
class ResearchExport extends Command
{
    protected $signature = 'research:export';

    protected $description = 'Snapshot the local research-data tables into database/research-data/*.json for committing to the repo';

    private const PILLARS = [
        'definitions', 'structure', 'authority', 'machine_readable', 'answerability',
        'eeat', 'citations', 'ai_accessibility', 'freshness', 'readability',
        'question_coverage', 'multimedia',
    ];

    private const STAGES = ['discovery', 'filter', 'compare', 'purchase'];

    public function handle(): int
    {
        $outputDir = base_path('database/research-data');
        if (! is_dir($outputDir) && ! mkdir($outputDir, 0775, true) && ! is_dir($outputDir)) {
            $this->error("Could not create directory: {$outputDir}");
            return Command::FAILURE;
        }

        $eeatPath = $outputDir.'/eeat-content-type.json';
        $eeatProps = $this->buildEeatProps();
        $this->writeJson($eeatPath, $eeatProps, 'eeat-content-type.json');

        $ecommercePath = $outputDir.'/ecommerce-recommendation-survival.json';
        $ecommerceProps = $this->buildEcommerceProps();
        $this->writeJson($ecommercePath, $ecommerceProps, 'ecommerce-recommendation-survival.json');

        $this->newLine();
        $this->info('Done. Commit database/research-data/*.json and deploy.');
        return Command::SUCCESS;
    }

    private function writeJson(string $path, array $data, string $label): void
    {
        if (empty($data) || (isset($data['headline']) && empty($data['headline']))) {
            $this->error("✗ {$label} — buildProps returned empty. Local DB probably doesn't have the study data.");
            return;
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $size = number_format(filesize($path) / 1024, 1);
        $this->info("✓ {$label}  ({$size} KB)");
    }

    // ─────────────────────────────────────────────────────────────
    // EeatStudy data — mirrors EeatStudyController::buildProps()
    // ─────────────────────────────────────────────────────────────

    private function buildEeatProps(): array
    {
        $entries = GeoStudyEntry::where('study_version', 'v5-eeat')
            ->where('status', 'completed')
            ->get();

        $v1Entries = GeoStudyEntry::where('study_version', 'v4-eeat')
            ->where('status', 'completed')
            ->get();

        return [
            'entries' => $entries->map(fn ($e) => [
                'domain' => $e->domain,
                'industry' => $e->industry,
                'content_type' => $e->content_type,
                'query' => $e->query,
                'citation_rate' => (float) $e->citation_rate,
                'platforms_cited' => $e->platforms_cited ?? [],
            ])->values()->toArray(),
            'cellSummaries' => $this->eeatCellSummaries($entries),
            'byContentType' => $this->eeatAggregateBy($entries, 'content_type'),
            'byIndustryPublic' => $this->eeatAggregateBy($entries, 'industry'),
            'citationDistribution' => $this->eeatCitationDistribution($entries),
            'headline' => $this->eeatHeadline($entries),
            'v1Summary' => [
                'byContentType' => $this->eeatAggregateBy($v1Entries, 'content_type'),
                'n' => $v1Entries->count(),
            ],
        ];
    }

    private function eeatCellSummaries($entries): array
    {
        $cells = [];
        foreach ($entries->groupBy(fn ($e) => $e->industry.'/'.$e->content_type) as $key => $group) {
            [$industry, $contentType] = explode('/', $key);
            $cells[] = [
                'industry' => $industry,
                'content_type' => $contentType,
                'n' => $group->count(),
                'avg_citation_rate' => round($group->avg('citation_rate'), 1),
            ];
        }
        return $cells;
    }

    private function eeatAggregateBy($entries, string $dimension): array
    {
        $rows = [];
        foreach ($entries->groupBy($dimension) as $key => $group) {
            $rows[] = [
                $dimension => $key,
                'n' => $group->count(),
                'avg_citation_rate' => round($group->avg('citation_rate'), 1),
            ];
        }
        return $rows;
    }

    private function eeatCitationDistribution($entries): array
    {
        $allCited = $entries->where('citation_rate', 100)->count();
        $noneCited = $entries->where('citation_rate', 0)->count();
        return [
            'all_cited' => $allCited,
            'none_cited' => $noneCited,
            'partial' => $entries->count() - $allCited - $noneCited,
            'total' => $entries->count(),
        ];
    }

    private function eeatHeadline($entries): array
    {
        if ($entries->isEmpty()) {
            return ['n' => 0, 'industries' => 0, 'content_types' => 0];
        }
        return [
            'n' => $entries->count(),
            'industries' => $entries->pluck('industry')->unique()->count(),
            'content_types' => $entries->pluck('content_type')->unique()->count(),
            'avg_citation_rate' => round($entries->avg('citation_rate'), 1),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // EcommerceJourney data — mirrors EcommerceJourneyController::buildProps()
    // ─────────────────────────────────────────────────────────────

    private function buildEcommerceProps(): array
    {
        $entries = GeoStudyEntry::where('study_version', 'v6-ecommerce')
            ->where('status', 'completed')
            ->get();
        $checks = CitationCheck::whereIn('citation_query_id', $entries->pluck('citation_query_id')->filter()->unique())
            ->where('status', 'completed')
            ->get();

        $brands = $this->ecommerceBrands($entries, $checks);

        return [
            'brands' => $brands,
            'headline' => $this->ecommerceHeadline($entries, $brands),
            'categorySummaries' => $this->ecommerceCategorySummaries($brands),
            'stageSummaries' => $this->ecommerceStageSummaries($entries),
            'stageStrengthDecay' => $this->ecommerceStageStrengthDecay($entries, $checks),
            'mentionTypeByStage' => $this->ecommerceMentionTypeByStage($entries, $checks),
            'strengthByCategory' => $this->ecommerceStrengthByCategory($brands),
            'platformAgreement' => $this->ecommercePlatformAgreement($entries, $checks),
            'strengthDistribution' => $this->ecommerceStrengthDistribution($brands),
            'pillarRecommendation' => ['available' => count($brands) >= 5],
        ];
    }

    private function ecommerceBrands($entries, $checks): array
    {
        $checksByQid = $checks->groupBy('citation_query_id');
        $brands = [];
        foreach ($entries->groupBy('domain') as $domain => $brandEntries) {
            $stages = [];
            $stageStrengths = [];
            $allStrengths = [];
            $topPickCount = $recommendedCount = $totalAnalyzed = 0;
            foreach ($brandEntries as $e) {
                $stages[$e->content_type] = (float) $e->citation_rate;
                $entryChecks = $checksByQid->get($e->citation_query_id, collect());
                $entryStrengths = [];
                foreach ($entryChecks as $c) {
                    if ($c->recommendation_strength === null) continue;
                    $entryStrengths[] = (float) $c->recommendation_strength;
                    $allStrengths[] = (float) $c->recommendation_strength;
                    $totalAnalyzed++;
                    if (Arr::get($c->mention_analysis ?? [], 'is_top_pick')) $topPickCount++;
                    if (Arr::get($c->mention_analysis ?? [], 'mention_type') === 'recommended') $recommendedCount++;
                }
                $stageStrengths[$e->content_type] = count($entryStrengths) > 0
                    ? round(array_sum($entryStrengths) / count($entryStrengths), 1)
                    : null;
            }
            $first = $brandEntries->first();
            $pillars = $first->pillar_scores ?? [];
            $rates = array_values($stages);
            $brands[] = [
                'domain' => $domain,
                'category' => $first->category,
                'geo_score' => $first->geo_score,
                'pillars' => collect(self::PILLARS)->mapWithKeys(fn ($p) => [$p => $pillars[$p]['percentage'] ?? null])->toArray(),
                'stage_rates' => [
                    'discovery' => $stages['discovery'] ?? 0,
                    'filter' => $stages['filter'] ?? 0,
                    'compare' => $stages['compare'] ?? 0,
                    'purchase' => $stages['purchase'] ?? 0,
                ],
                'stage_strengths' => [
                    'discovery' => $stageStrengths['discovery'] ?? null,
                    'filter' => $stageStrengths['filter'] ?? null,
                    'compare' => $stageStrengths['compare'] ?? null,
                    'purchase' => $stageStrengths['purchase'] ?? null,
                ],
                'survival_rate' => count($rates) > 0 ? round(array_sum($rates) / count($rates), 1) : 0,
                'strength_score' => count($allStrengths) > 0 ? round(array_sum($allStrengths) / count($allStrengths), 1) : 0,
                'top_pick_rate' => $totalAnalyzed > 0 ? round($topPickCount / $totalAnalyzed * 100, 1) : 0,
                'recommended_rate' => $totalAnalyzed > 0 ? round($recommendedCount / $totalAnalyzed * 100, 1) : 0,
                'survived_to_purchase' => ($stages['purchase'] ?? 0) > 0,
                'survived_discovery' => ($stages['discovery'] ?? 0) > 0,
                'survived_all_stages' => collect($stages)->every(fn ($r) => $r > 0),
            ];
        }
        usort($brands, fn ($a, $b) => $b['survival_rate'] <=> $a['survival_rate']);
        return $brands;
    }

    private function ecommerceHeadline($entries, array $brands): array
    {
        $totalBrands = count($brands);
        $survivedPurchase = collect($brands)->filter(fn ($b) => $b['survived_to_purchase'])->count();
        $survivedAll = collect($brands)->filter(fn ($b) => $b['survived_all_stages'])->count();
        return [
            'total_brands' => $totalBrands,
            'total_entries' => $entries->count(),
            'total_checks' => $entries->sum('citations_checked'),
            'categories' => $entries->pluck('category')->unique()->count(),
            'avg_survival_rate' => $totalBrands > 0 ? round(collect($brands)->avg('survival_rate'), 1) : 0,
            'avg_strength_score' => $totalBrands > 0 ? round(collect($brands)->avg('strength_score'), 1) : 0,
            'survived_purchase_pct' => $totalBrands > 0 ? round($survivedPurchase / $totalBrands * 100, 1) : 0,
            'survived_all_stages_pct' => $totalBrands > 0 ? round($survivedAll / $totalBrands * 100, 1) : 0,
            'strength_analyzed' => collect($brands)->filter(fn ($b) => $b['strength_score'] > 0 || $b['top_pick_rate'] > 0 || $b['recommended_rate'] > 0)->count(),
        ];
    }

    private function ecommerceCategorySummaries(array $brands): array
    {
        $rows = [];
        foreach (collect($brands)->groupBy('category') as $cat => $group) {
            $rows[] = [
                'category' => $cat,
                'n' => $group->count(),
                'avg_survival' => round($group->avg('survival_rate'), 1),
                'avg_geo' => round($group->avg('geo_score'), 1),
                'purchase_survival_pct' => round($group->filter(fn ($b) => $b['survived_to_purchase'])->count() / $group->count() * 100, 1),
            ];
        }
        usort($rows, fn ($a, $b) => $b['avg_survival'] <=> $a['avg_survival']);
        return $rows;
    }

    private function ecommerceStageSummaries($entries): array
    {
        $rows = [];
        foreach (self::STAGES as $stage) {
            $stageEntries = $entries->where('content_type', $stage);
            $rows[] = [
                'stage' => $stage,
                'n' => $stageEntries->count(),
                'avg_citation_rate' => round($stageEntries->avg('citation_rate'), 1),
                'cited_count' => $stageEntries->where('citations_cited', '>', 0)->count(),
            ];
        }
        return $rows;
    }

    private function ecommerceStageStrengthDecay($entries, $checks): array
    {
        $checksByQid = $checks->groupBy('citation_query_id');
        $rows = [];
        foreach (self::STAGES as $stage) {
            $strengths = [];
            foreach ($entries->where('content_type', $stage) as $e) {
                foreach ($checksByQid->get($e->citation_query_id, collect()) as $c) {
                    if ($c->recommendation_strength !== null) $strengths[] = (float) $c->recommendation_strength;
                }
            }
            $rows[] = [
                'stage' => $stage,
                'avg_strength' => count($strengths) > 0 ? round(array_sum($strengths) / count($strengths), 1) : 0,
                'n_checks' => count($strengths),
            ];
        }
        return $rows;
    }

    private function ecommerceMentionTypeByStage($entries, $checks): array
    {
        $checksByQid = $checks->groupBy('citation_query_id');
        $rows = [];
        foreach (self::STAGES as $stage) {
            $counts = ['recommended' => 0, 'neutral' => 0, 'negative' => 0, 'absent' => 0];
            $total = 0;
            foreach ($entries->where('content_type', $stage) as $e) {
                foreach ($checksByQid->get($e->citation_query_id, collect()) as $c) {
                    $type = $c->mention_analysis['mention_type'] ?? null;
                    if ($type === null) continue;
                    $counts[$type] = ($counts[$type] ?? 0) + 1;
                    $total++;
                }
            }
            $rows[] = array_merge(['stage' => $stage], $counts, ['total' => $total]);
        }
        return $rows;
    }

    private function ecommerceStrengthByCategory(array $brands): array
    {
        $rows = [];
        foreach (collect($brands)->groupBy('category') as $cat => $group) {
            $rows[] = [
                'category' => $cat,
                'n' => $group->count(),
                'avg_strength' => round($group->avg('strength_score'), 1),
                'avg_survival' => round($group->avg('survival_rate'), 1),
            ];
        }
        usort($rows, fn ($a, $b) => $b['avg_strength'] <=> $a['avg_strength']);
        return $rows;
    }

    private function ecommercePlatformAgreement($entries, $checks): array
    {
        $checksByQid = $checks->groupBy('citation_query_id');
        $unanimousYes = $unanimousNo = $split = $total = 0;
        foreach ($entries as $e) {
            $cs = $checksByQid->get($e->citation_query_id, collect());
            $platforms = $cs->where('status', 'completed')->pluck('is_cited');
            if ($platforms->count() < 2) continue;
            $citedCount = $platforms->filter()->count();
            $total++;
            if ($citedCount === $platforms->count()) $unanimousYes++;
            elseif ($citedCount === 0) $unanimousNo++;
            else $split++;
        }
        return compact('unanimousYes', 'unanimousNo', 'split', 'total');
    }

    private function ecommerceStrengthDistribution(array $brands): array
    {
        $buckets = [
            ['range' => '0-19', 'count' => 0, 'min' => 0, 'max' => 19],
            ['range' => '20-39', 'count' => 0, 'min' => 20, 'max' => 39],
            ['range' => '40-59', 'count' => 0, 'min' => 40, 'max' => 59],
            ['range' => '60-79', 'count' => 0, 'min' => 60, 'max' => 79],
            ['range' => '80-100', 'count' => 0, 'min' => 80, 'max' => 100],
        ];
        foreach ($brands as $b) {
            $s = $b['strength_score'] ?? 0;
            foreach ($buckets as $i => $bucket) {
                if ($s >= $bucket['min'] && $s <= $bucket['max']) {
                    $buckets[$i]['count']++;
                    break;
                }
            }
        }
        return $buckets;
    }
}
