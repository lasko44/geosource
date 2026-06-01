<?php

namespace App\Console\Commands;

use App\Jobs\CheckCitationJob;
use App\Jobs\ScanWebsiteJob;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\GeoStudyEntry;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs the GEO effectiveness study by scanning URLs and checking citations.
 */
class RunGeoStudy extends Command
{
    protected $signature = 'study:run
                            {phase=seed : Phase to run: seed, scan, cite, collect, report}
                            {--study=v2 : Study version identifier}
                            {--limit=0 : Max entries to process (0 = all)}
                            {--delay=10 : Seconds between dispatched jobs}
                            {--tier=basic : Scan tier to use (basic, pro, full)}
                            {--platforms=perplexity,openai,claude : Comma-separated platforms for citation checks}';

    protected $description = 'Run the GEO effectiveness study correlating GEO scores with AI citation rates';

    public function handle(): int
    {
        $phase = $this->argument('phase');
        $this->version = $this->option('study');
        $this->info("Study version: {$this->version}");

        return match ($phase) {
            'seed' => $this->seed(),
            'scan' => $this->scan(),
            'cite' => $this->cite(),
            'collect' => $this->collect(),
            'report' => $this->report(),
            default => $this->error("Unknown phase: {$phase}") ?? Command::FAILURE,
        };
    }

    private string $version = 'v2';

    /**
     * Seed the study table from the config file.
     */
    private function seed(): int
    {
        $entries = config('geo-study.entries', []);

        if (empty($entries)) {
            $this->error('No entries found in config/geo-study.php');

            return Command::FAILURE;
        }

        $existing = GeoStudyEntry::where('study_version', $this->version)->count();
        if ($existing > 0) {
            if (! $this->confirm("Study {$this->version} already has {$existing} entries. Add more?")) {
                return Command::SUCCESS;
            }
        }

        $added = 0;
        foreach ($entries as $entry) {
            $exists = GeoStudyEntry::where('url', $entry['url'])
                ->where('query', $entry['query'])
                ->where('study_version', $this->version)
                ->exists();

            if (! $exists) {
                GeoStudyEntry::create([
                    'study_version' => $this->version,
                    'url' => $entry['url'],
                    'domain' => $entry['domain'],
                    'industry' => $entry['industry'],
                    'site_size' => $entry['site_size'],
                    'query' => $entry['query'],
                    'status' => 'pending',
                ]);
                $added++;
            }
        }

        $this->info("Seeded {$added} new entries for {$this->version}. Total: ".GeoStudyEntry::where('study_version', $this->version)->count());

        return Command::SUCCESS;
    }

    /**
     * Run GEO scans on all pending entries.
     */
    private function scan(): int
    {
        $admin = User::where('is_admin', true)->first();
        if (! $admin) {
            $this->error('No admin user found. Need an admin to run scans without tokens.');

            return Command::FAILURE;
        }

        $tier = $this->option('tier');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');

        $query = GeoStudyEntry::where('status', 'pending')->where('study_version', $this->version);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            $this->info("No pending entries to scan for {$this->version}.");

            return Command::SUCCESS;
        }

        $this->info("Dispatching {$entries->count()} scans as {$tier} tier...");

        $bar = $this->output->createProgressBar($entries->count());
        $bar->start();

        $dispatched = 0;
        foreach ($entries as $entry) {
            try {
                $scan = Scan::create([
                    'user_id' => $admin->id,
                    'url' => $entry->url,
                    'title' => parse_url($entry->url, PHP_URL_HOST),
                    'status' => 'pending',
                    'requested_tier' => $tier,
                    'tokens_charged' => false,
                    'tokens_amount' => 0,
                ]);

                $entry->update([
                    'scan_id' => $scan->id,
                    'status' => 'scanning',
                ]);

                ScanWebsiteJob::dispatch($scan)->delay(now()->addSeconds($delay * $dispatched));
                $dispatched++;
            } catch (\Exception $e) {
                $entry->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("Failed {$entry->url}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Dispatched {$dispatched} scan jobs. They will process in the background.");
        $this->info("Run 'php artisan study:run collect' after scans complete to gather results.");

        return Command::SUCCESS;
    }

    /**
     * Run citation checks on scanned entries.
     */
    private function cite(): int
    {
        $admin = User::where('is_admin', true)->first();
        if (! $admin) {
            $this->error('No admin user found.');

            return Command::FAILURE;
        }

        $platforms = explode(',', $this->option('platforms'));
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');

        $query = GeoStudyEntry::where('status', 'scanned')->where('study_version', $this->version);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            $this->info("No scanned entries ready for citation checks for {$this->version}. Run \"collect\" first.");

            return Command::SUCCESS;
        }

        $this->info("Dispatching citation checks for {$entries->count()} entries across ".count($platforms).' platforms...');

        $bar = $this->output->createProgressBar($entries->count());
        $bar->start();

        $dispatched = 0;
        foreach ($entries as $entry) {
            try {
                $citationQuery = CitationQuery::create([
                    'user_id' => $admin->id,
                    'query' => $entry->query,
                    'domain' => $entry->domain,
                    'frequency' => CitationQuery::FREQUENCY_MANUAL,
                    'is_active' => false,
                ]);

                $entry->update([
                    'citation_query_id' => $citationQuery->id,
                    'status' => 'checking_citations',
                ]);

                foreach ($platforms as $platform) {
                    $platform = trim($platform);
                    $check = CitationCheck::create([
                        'citation_query_id' => $citationQuery->id,
                        'user_id' => $admin->id,
                        'platform' => $platform,
                        'status' => CitationCheck::STATUS_PENDING,
                    ]);

                    CheckCitationJob::dispatch($check)
                        ->delay(now()->addSeconds($delay * $dispatched));
                }

                $dispatched++;
            } catch (\Exception $e) {
                $entry->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("Failed {$entry->domain}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Dispatched citation checks for {$dispatched} entries.");
        $this->info("Run 'php artisan study:run collect' after checks complete to gather results.");

        return Command::SUCCESS;
    }

    /**
     * Collect results from completed scans and citation checks.
     */
    private function collect(): int
    {
        // Collect scan results
        $scanningEntries = GeoStudyEntry::where('status', 'scanning')
            ->where('study_version', $this->version)
            ->whereNotNull('scan_id')
            ->get();

        $scansCompleted = 0;
        $scansFailed = 0;
        foreach ($scanningEntries as $entry) {
            $scan = $entry->scan;
            if (! $scan) {
                continue;
            }

            if ($scan->status === 'completed') {
                $results = $scan->results ?? [];

                $entry->update([
                    'geo_score' => (int) round($scan->score),
                    'geo_grade' => $scan->grade,
                    'pillar_scores' => $results['pillar_scores'] ?? $results['pillars'] ?? null,
                    'status' => 'scanned',
                ]);
                $scansCompleted++;
            } elseif ($scan->status === 'failed') {
                $entry->update([
                    'status' => 'failed',
                    'error_message' => 'Scan failed: '.($scan->error_message ?? 'Unknown error'),
                ]);
                $scansFailed++;
            }
        }

        // Collect citation results
        $citationEntries = GeoStudyEntry::where('status', 'checking_citations')
            ->where('study_version', $this->version)
            ->whereNotNull('citation_query_id')
            ->get();

        $citationsCompleted = 0;
        foreach ($citationEntries as $entry) {
            $checks = CitationCheck::where('citation_query_id', $entry->citation_query_id)->get();

            $allDone = $checks->every(fn ($c) => in_array($c->status, ['completed', 'failed']));
            if (! $allDone) {
                continue;
            }

            $completedChecks = $checks->where('status', 'completed');
            $citedChecks = $completedChecks->where('is_cited', true);

            $platformsCited = $citedChecks->pluck('platform')->values()->toArray();
            $totalChecked = $completedChecks->count();
            $totalCited = $citedChecks->count();
            $citationRate = $totalChecked > 0 ? round(($totalCited / $totalChecked) * 100, 2) : 0;

            $entry->update([
                'citations_checked' => $totalChecked,
                'citations_cited' => $totalCited,
                'citation_rate' => $citationRate,
                'platforms_cited' => $platformsCited,
                'status' => 'completed',
            ]);
            $citationsCompleted++;
        }

        $this->info("Scan results collected: {$scansCompleted} completed, {$scansFailed} failed, ".($scanningEntries->count() - $scansCompleted - $scansFailed).' still processing.');
        $this->info("Citation results collected: {$citationsCompleted} completed, ".($citationEntries->count() - $citationsCompleted).' still processing.');

        // Show summary
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            GeoStudyEntry::where('study_version', $this->version)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->map(fn ($row) => [$row->status, $row->count])
                ->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Generate the study report.
     */
    private function report(): int
    {
        $completed = GeoStudyEntry::where('status', 'completed')->where('study_version', $this->version)->get();

        if ($completed->isEmpty()) {
            $this->error("No completed entries for {$this->version}. Run seed → scan → collect → cite → collect → report.");

            return Command::FAILURE;
        }

        $this->info("=== GEO EFFECTIVENESS STUDY REPORT ({$this->version}) ===\n");
        $this->info("Total completed entries: {$completed->count()}");
        $this->newLine();

        // Overall correlation
        $avgScoreCited = $completed->where('citations_cited', '>', 0)->avg('geo_score');
        $avgScoreNotCited = $completed->where('citations_cited', 0)->avg('geo_score');

        $this->info('--- HEADLINE FINDING ---');
        $this->info(sprintf('Avg GEO score of cited sites:     %.1f', $avgScoreCited ?? 0));
        $this->info(sprintf('Avg GEO score of non-cited sites: %.1f', $avgScoreNotCited ?? 0));

        if ($avgScoreCited && $avgScoreNotCited && $avgScoreNotCited > 0) {
            $multiplier = round($avgScoreCited / $avgScoreNotCited, 1);
            $this->info("Sites that get cited have {$multiplier}x higher GEO scores on average.");
        }
        $this->newLine();

        // By score bracket
        $this->info('--- CITATION RATE BY GEO SCORE BRACKET ---');
        $brackets = [
            '0-20' => [0, 20],
            '21-40' => [21, 40],
            '41-60' => [41, 60],
            '61-80' => [61, 80],
            '81-100' => [81, 100],
        ];

        $bracketData = [];
        foreach ($brackets as $label => [$min, $max]) {
            $group = $completed->filter(fn ($e) => $e->geo_score >= $min && $e->geo_score <= $max);
            if ($group->isEmpty()) {
                continue;
            }

            $bracketData[] = [
                $label,
                $group->count(),
                sprintf('%.1f', $group->avg('geo_score')),
                sprintf('%.1f%%', $group->avg('citation_rate')),
                $group->where('citations_cited', '>', 0)->count().'/'.$group->count(),
            ];
        }

        $this->table(['Score Range', 'Sites', 'Avg Score', 'Avg Citation Rate', 'Cited/Total'], $bracketData);

        // By industry
        $this->info('--- CITATION RATE BY INDUSTRY ---');
        $industryData = [];
        foreach ($completed->groupBy('industry') as $industry => $group) {
            $industryData[] = [
                $industry,
                $group->count(),
                sprintf('%.1f', $group->avg('geo_score')),
                sprintf('%.1f%%', $group->avg('citation_rate')),
            ];
        }

        usort($industryData, fn ($a, $b) => $b[3] <=> $a[3]);
        $this->table(['Industry', 'Sites', 'Avg GEO Score', 'Avg Citation Rate'], $industryData);

        // By site size
        $this->info('--- CITATION RATE BY SITE SIZE ---');
        $sizeData = [];
        foreach ($completed->groupBy('site_size') as $size => $group) {
            $sizeData[] = [
                $size,
                $group->count(),
                sprintf('%.1f', $group->avg('geo_score')),
                sprintf('%.1f%%', $group->avg('citation_rate')),
            ];
        }

        $this->table(['Size', 'Sites', 'Avg GEO Score', 'Avg Citation Rate'], $sizeData);

        // Platform breakdown
        $this->info('--- CITATION RATE BY AI PLATFORM ---');
        $platformCounts = [];
        foreach ($completed as $entry) {
            foreach ($entry->platforms_cited ?? [] as $platform) {
                $platformCounts[$platform] = ($platformCounts[$platform] ?? 0) + 1;
            }
        }

        arsort($platformCounts);
        $platformData = [];
        foreach ($platformCounts as $platform => $count) {
            $platformData[] = [
                $platform,
                $count,
                sprintf('%.1f%%', ($count / $completed->count()) * 100),
            ];
        }

        $this->table(['Platform', 'Sites Cited', '% of Total'], $platformData);

        // Top cited sites
        $this->newLine();
        $this->info('--- TOP 10 MOST CITED SITES ---');
        $topCited = $completed->sortByDesc('citation_rate')->take(10);
        $topData = [];
        foreach ($topCited as $entry) {
            $topData[] = [
                $entry->domain,
                $entry->geo_score,
                $entry->geo_grade,
                sprintf('%.0f%%', $entry->citation_rate),
                implode(', ', $entry->platforms_cited ?? []),
            ];
        }

        $this->table(['Domain', 'GEO Score', 'Grade', 'Citation Rate', 'Platforms'], $topData);

        // Bottom sites (not cited)
        $this->newLine();
        $this->info('--- SITES NOT CITED BY ANY PLATFORM ---');
        $notCited = $completed->where('citations_cited', 0)->sortBy('geo_score')->take(10);
        $notCitedData = [];
        foreach ($notCited as $entry) {
            $notCitedData[] = [
                $entry->domain,
                $entry->geo_score,
                $entry->geo_grade,
                $entry->industry,
            ];
        }

        $this->table(['Domain', 'GEO Score', 'Grade', 'Industry'], $notCitedData);

        return Command::SUCCESS;
    }
}
