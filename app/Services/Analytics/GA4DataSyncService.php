<?php

namespace App\Services\Analytics;

use App\Models\GA4Connection;
use App\Models\GA4ReferralData;
use Illuminate\Support\Facades\Log;

class GA4DataSyncService
{
    public function __construct(
        protected GA4Service $ga4Service
    ) {}

    /**
     * Sync referral data for a connection.
     */
    public function syncConnection(GA4Connection $connection, ?int $days = null): array
    {
        if (! $connection->is_active) {
            return [
                'success' => false,
                'error' => 'Connection is not active',
                'synced' => 0,
            ];
        }

        // Determine date range
        $days = $days ?? ($connection->last_synced_at
            ? config('citations.ga4_sync.daily_sync_days', 7)
            : config('citations.ga4_sync.initial_sync_days', 30));

        $endDate = now()->format('Y-m-d');
        $startDate = now()->subDays($days)->format('Y-m-d');

        try {
            // Get AI referral data
            $reportData = $this->ga4Service->getAIReferralData($connection, $startDate, $endDate);

            $synced = $this->processReportData($connection, $reportData);

            // Mark as synced
            $connection->markAsSynced();

            return [
                'success' => true,
                'synced' => $synced,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('GA4 sync failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0,
            ];
        }
    }

    /**
     * Process report data and store in database.
     */
    protected function processReportData(GA4Connection $connection, array $reportData): int
    {
        $rows = $reportData['rows'] ?? [];
        $synced = 0;

        foreach ($rows as $row) {
            $dimensions = $this->extractDimensions($row, $reportData);
            $metrics = $this->extractMetrics($row, $reportData);

            GA4ReferralData::upsertReferralData(
                $connection->id,
                $connection->team_id,
                $dimensions['date'],
                $dimensions['source'],
                [
                    'medium' => $dimensions['medium'] ?? 'referral',
                    'sessions' => (int) ($metrics['sessions'] ?? 0),
                    'users' => (int) ($metrics['totalUsers'] ?? 0),
                    'pageviews' => (int) ($metrics['screenPageViews'] ?? 0),
                    'engaged_sessions' => (int) ($metrics['engagedSessions'] ?? 0),
                    'bounce_rate' => (float) ($metrics['bounceRate'] ?? 0),
                    'avg_session_duration' => (float) ($metrics['averageSessionDuration'] ?? 0),
                ]
            );

            $synced++;
        }

        return $synced;
    }

    /**
     * Extract dimensions from a report row.
     */
    protected function extractDimensions(array $row, array $reportData): array
    {
        $dimensions = [];
        $dimensionHeaders = $reportData['dimensionHeaders'] ?? [];

        foreach ($row['dimensionValues'] ?? [] as $index => $value) {
            $headerName = $dimensionHeaders[$index]['name'] ?? "dim_{$index}";
            $dimensions[$this->normalizeDimensionName($headerName)] = $value['value'] ?? '';
        }

        return $dimensions;
    }

    /**
     * Extract metrics from a report row.
     */
    protected function extractMetrics(array $row, array $reportData): array
    {
        $metrics = [];
        $metricHeaders = $reportData['metricHeaders'] ?? [];

        foreach ($row['metricValues'] ?? [] as $index => $value) {
            $headerName = $metricHeaders[$index]['name'] ?? "metric_{$index}";
            $metrics[$headerName] = $value['value'] ?? 0;
        }

        return $metrics;
    }

    /**
     * Normalize dimension names to our internal format.
     */
    protected function normalizeDimensionName(string $name): string
    {
        return match ($name) {
            'sessionSource' => 'source',
            'sessionMedium' => 'medium',
            default => $name,
        };
    }

    /**
     * Sync all active connections.
     */
    public function syncAllConnections(): array
    {
        $connections = GA4Connection::where('is_active', true)->get();
        $results = [];

        foreach ($connections as $connection) {
            $results[$connection->id] = $this->syncConnection($connection);
        }

        return $results;
    }

    /**
     * Clean up old referral data.
     */
    public function cleanup(): int
    {
        $days = config('citations.ga4_sync.cleanup_after_days', 365);
        $cutoffDate = now()->subDays($days);

        return GA4ReferralData::where('date', '<', $cutoffDate)->delete();
    }

    /**
     * Get AI traffic summary for a connection.
     */
    public function getAITrafficSummary(GA4Connection $connection, int $days = 30): array
    {
        return $connection->getAITrafficSummary($days);
    }

    /**
     * Get daily AI traffic trend for a connection.
     */
    public function getDailyAITrafficTrend(GA4Connection $connection, int $days = 30): array
    {
        $aiSources = config('citations.ai_referral_sources', []);

        return $connection->referralData()
            ->where('date', '>=', now()->subDays($days))
            ->whereIn('source', $aiSources)
            ->selectRaw('date, SUM(sessions) as sessions, SUM(users) as users, SUM(pageviews) as pageviews')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->format('Y-m-d'),
                'sessions' => $row->sessions,
                'users' => $row->users,
                'pageviews' => $row->pageviews,
            ])
            ->toArray();
    }
}
