<?php

namespace App\Services\GeoStudy;

use App\Jobs\GeoStudy\ProcessGeoStudyJob;
use App\Models\GeoStudy;
use App\Models\GeoStudyResult;
use App\Models\GeoStudyUrl;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeoStudyService
{
    public function __construct(
        private UrlCollectorService $urlCollector,
        private GeoStudyAggregatorService $aggregator,
    ) {}

    /**
     * Create a new GEO study.
     */
    public function create(array $data, ?User $user = null): GeoStudy
    {
        return GeoStudy::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? GeoStudy::CATEGORY_MIXED,
            'source_type' => $data['source_type'] ?? GeoStudy::SOURCE_CSV,
            'source_config' => $data['source_config'] ?? null,
            'status' => GeoStudy::STATUS_DRAFT,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Import URLs to a study from CSV content.
     */
    public function importUrlsFromCsv(GeoStudy $study, string $csvContent): int
    {
        $urls = $this->urlCollector->parseCSV($csvContent);

        return $this->addUrls($study, $urls, GeoStudy::SOURCE_CSV);
    }

    /**
     * Import URLs from SERP API.
     */
    public function importUrlsFromSerp(GeoStudy $study, array $keywords, int $perKeyword = 10): int
    {
        $study->update([
            'status' => GeoStudy::STATUS_COLLECTING,
            'source_config' => ['keywords' => $keywords, 'per_keyword' => $perKeyword],
        ]);

        $urls = $this->urlCollector->collectFromSerp($keywords, $perKeyword);

        $count = $this->addUrls($study, $urls, GeoStudy::SOURCE_SERP);

        $study->update(['status' => GeoStudy::STATUS_DRAFT]);

        return $count;
    }

    /**
     * Import URLs from sitemap.
     */
    public function importUrlsFromSitemap(GeoStudy $study, string $sitemapUrl, int $limit = 500): int
    {
        $study->update([
            'status' => GeoStudy::STATUS_COLLECTING,
            'source_config' => ['sitemap_url' => $sitemapUrl, 'limit' => $limit],
        ]);

        $urls = $this->urlCollector->collectFromSitemap($sitemapUrl, $limit);

        $count = $this->addUrls($study, $urls, GeoStudy::SOURCE_SITEMAP);

        $study->update(['status' => GeoStudy::STATUS_DRAFT]);

        return $count;
    }

    /**
     * Add URLs to a study.
     */
    public function addUrls(GeoStudy $study, array $urls, string $sourceType): int
    {
        $added = 0;

        // Get existing URLs to avoid duplicates
        $existingUrls = $study->urls()->pluck('url')->toArray();

        DB::transaction(function () use ($study, $urls, $sourceType, $existingUrls, &$added) {
            foreach ($urls as $url) {
                $urlString = is_array($url) ? $url['url'] : $url;
                $metadata = is_array($url) ? ($url['metadata'] ?? null) : null;

                // Skip duplicates
                if (in_array($urlString, $existingUrls)) {
                    continue;
                }

                GeoStudyUrl::create([
                    'geo_study_id' => $study->id,
                    'url' => $urlString,
                    'source_type' => $sourceType,
                    'metadata' => $metadata,
                    'status' => GeoStudyUrl::STATUS_PENDING,
                ]);

                $added++;
            }

            $study->update(['total_urls' => $study->urls()->count()]);
        });

        return $added;
    }

    /**
     * Start processing a study.
     */
    public function start(GeoStudy $study): bool
    {
        if (! $study->canStart()) {
            return false;
        }

        $study->update([
            'status' => GeoStudy::STATUS_PROCESSING,
            'started_at' => now(),
            'processed_urls' => 0,
            'failed_urls' => 0,
            'progress_percent' => 0,
        ]);

        // Create result records for each URL
        $this->createResultRecords($study);

        // Dispatch the batch processing job
        ProcessGeoStudyJob::dispatch($study);

        return true;
    }

    /**
     * Create result records for all pending URLs.
     */
    private function createResultRecords(GeoStudy $study): void
    {
        $study->urls()
            ->where('status', GeoStudyUrl::STATUS_PENDING)
            ->each(function (GeoStudyUrl $url) use ($study) {
                // Mark URL as queued
                $url->markQueued();

                // Create a pending result record
                GeoStudyResult::create([
                    'geo_study_id' => $study->id,
                    'url' => $url->url,
                    'source_type' => $url->source_type,
                    'source_metadata' => $url->metadata,
                    'status' => GeoStudyResult::STATUS_PENDING,
                ]);
            });
    }

    /**
     * Cancel a running study.
     */
    public function cancel(GeoStudy $study): bool
    {
        if (! $study->canCancel()) {
            return false;
        }

        // Cancel the Laravel batch if exists
        if ($study->batch_id) {
            try {
                $batch = Bus::findBatch($study->batch_id);
                $batch?->cancel();
            } catch (\Exception $e) {
                Log::warning("Failed to cancel batch {$study->batch_id}: {$e->getMessage()}");
            }
        }

        $study->update([
            'status' => GeoStudy::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark study as completed and aggregate results.
     */
    public function complete(GeoStudy $study): void
    {
        $study->updateProgress();

        // Aggregate all results
        $aggregation = $this->aggregator->aggregate($study);

        $study->update([
            'status' => GeoStudy::STATUS_COMPLETED,
            'completed_at' => now(),
            'aggregate_stats' => $aggregation['stats'],
            'category_breakdown' => $aggregation['category_breakdown'],
            'pillar_analysis' => $aggregation['pillar_analysis'],
            'top_performers' => $aggregation['top_performers'],
            'bottom_performers' => $aggregation['bottom_performers'],
        ]);
    }

    /**
     * Mark study as failed.
     */
    public function fail(GeoStudy $study, string $errorMessage): void
    {
        $study->updateProgress();

        $study->update([
            'status' => GeoStudy::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Delete a study and all related data.
     */
    public function delete(GeoStudy $study): bool
    {
        // Cancel if in progress
        if ($study->isInProgress()) {
            $this->cancel($study);
        }

        // Soft delete
        return $study->delete();
    }
}
