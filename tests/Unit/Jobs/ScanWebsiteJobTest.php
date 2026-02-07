<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanWebsiteJobTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Competitor Benchmark Calculation Tests
    // ==========================================

    public function test_competitor_benchmark_calculates_dominant_position(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 90 (should be dominant over competitors averaging 60)
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 90,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans with lower scores
        $this->createCompetitorScans($parentScan, [55, 60, 65, 58, 62]);

        // Simulate the last competitor scan completing
        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);

        // Use reflection to call private method
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        // Refresh parent scan
        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('dominant', $benchmark['position']);
        $this->assertEquals(100, $benchmark['percentile']); // Beat all 5 competitors
        $this->assertEquals(5, $benchmark['competitors_analyzed']);
        $this->assertGreaterThan(15, $benchmark['score_difference']);
        $this->assertStringContainsString('Outstanding', $benchmark['comparison']);
    }

    public function test_competitor_benchmark_calculates_ahead_position(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 75 (should be ahead of competitors averaging 67)
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 75,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans with slightly lower scores (avg ~67)
        $this->createCompetitorScans($parentScan, [65, 68, 70, 64, 68]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('ahead', $benchmark['position']);
        $this->assertGreaterThanOrEqual(5, $benchmark['score_difference']);
        $this->assertLessThan(15, $benchmark['score_difference']);
        $this->assertStringContainsString('Strong position', $benchmark['comparison']);
    }

    public function test_competitor_benchmark_calculates_competitive_position(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 70 (should be competitive with competitors averaging 72)
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 70,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans with similar scores (avg ~72)
        $this->createCompetitorScans($parentScan, [70, 72, 74, 71, 73]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('competitive', $benchmark['position']);
        $this->assertGreaterThanOrEqual(-5, $benchmark['score_difference']);
        $this->assertLessThan(5, $benchmark['score_difference']);
        $this->assertStringContainsString('competitive', $benchmark['comparison']);
    }

    public function test_competitor_benchmark_calculates_behind_position(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 60 (should be behind competitors averaging 72)
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 60,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans with higher scores (avg ~72)
        $this->createCompetitorScans($parentScan, [70, 72, 74, 71, 73]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('behind', $benchmark['position']);
        $this->assertLessThan(-5, $benchmark['score_difference']);
        $this->assertGreaterThanOrEqual(-15, $benchmark['score_difference']);
        $this->assertStringContainsString('behind competitors', $benchmark['comparison']);
    }

    public function test_competitor_benchmark_calculates_far_behind_position(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 40 (should be far behind competitors averaging 80)
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 40,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans with much higher scores (avg ~80)
        $this->createCompetitorScans($parentScan, [78, 80, 82, 79, 81]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('far_behind', $benchmark['position']);
        $this->assertLessThan(-15, $benchmark['score_difference']);
        $this->assertStringContainsString('Significant gap', $benchmark['comparison']);
    }

    public function test_competitor_benchmark_handles_no_completed_competitors(): void
    {
        $user = User::factory()->create();

        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 75,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create competitor scans that all failed (no completed ones)
        // Use score of 0 since database requires non-null, but status=failed means they won't be included
        for ($i = 0; $i < 3; $i++) {
            Scan::factory()->create([
                'user_id' => $user->id,
                'parent_scan_id' => $parentScan->id,
                'is_competitor' => true,
                'status' => 'failed',
                'score' => 0,
            ]);
        }

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals('no_competitors', $benchmark['position']);
        $this->assertNull($benchmark['percentile']);
        $this->assertNull($benchmark['avg_competitor_score']);
        $this->assertEquals(0, $benchmark['competitors_analyzed']);
    }

    public function test_competitor_benchmark_calculates_correct_percentile(): void
    {
        $user = User::factory()->create();

        // Parent scan with score of 70
        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 70,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create 5 competitor scans: 2 below (60, 65), 3 above (75, 80, 85)
        // User beats 2/5 = 40th percentile
        $this->createCompetitorScans($parentScan, [60, 65, 75, 80, 85]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $benchmark = $parentScan->results['competitor_benchmark'];
        $this->assertEquals(40, $benchmark['percentile']); // Beat 2 out of 5
    }

    public function test_parent_scan_status_updates_when_all_competitors_complete(): void
    {
        $user = User::factory()->create();

        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 75,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
            'competitors_found' => 0,
        ]);

        // Create competitor scans
        $this->createCompetitorScans($parentScan, [70, 72, 74]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        $this->assertEquals('completed', $parentScan->competitor_discovery_status);
        $this->assertEquals(3, $parentScan->competitors_found);
    }

    public function test_parent_scan_not_updated_when_competitors_still_pending(): void
    {
        $user = User::factory()->create();

        $parentScan = Scan::factory()->create([
            'user_id' => $user->id,
            'score' => 75,
            'status' => 'completed',
            'auto_find_competitors' => true,
            'competitor_discovery_status' => 'scanning',
        ]);

        // Create one completed and one pending competitor scan
        Scan::factory()->create([
            'user_id' => $user->id,
            'parent_scan_id' => $parentScan->id,
            'is_competitor' => true,
            'status' => 'completed',
            'score' => 70,
        ]);

        Scan::factory()->create([
            'user_id' => $user->id,
            'parent_scan_id' => $parentScan->id,
            'is_competitor' => true,
            'status' => 'pending',
            'score' => 0, // Pending scans won't have a real score yet
        ]);

        $lastCompetitor = Scan::where('parent_scan_id', $parentScan->id)
            ->where('status', 'completed')
            ->first();
        $job = new ScanWebsiteJob($lastCompetitor);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        $parentScan->refresh();

        // Should still be in scanning status since one competitor is pending
        $this->assertEquals('scanning', $parentScan->competitor_discovery_status);
    }

    public function test_non_competitor_scan_does_not_trigger_parent_update(): void
    {
        $user = User::factory()->create();

        $regularScan = Scan::factory()->create([
            'user_id' => $user->id,
            'is_competitor' => false,
            'parent_scan_id' => null,
        ]);

        $job = new ScanWebsiteJob($regularScan);
        $this->invokePrivateMethod($job, 'checkAndUpdateParentCompetitorStatus');

        // This should just return early without errors
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create competitor scans with given scores.
     */
    private function createCompetitorScans(Scan $parentScan, array $scores): void
    {
        foreach ($scores as $score) {
            Scan::factory()->create([
                'user_id' => $parentScan->user_id,
                'parent_scan_id' => $parentScan->id,
                'is_competitor' => true,
                'status' => 'completed',
                'score' => $score,
            ]);
        }
    }

    /**
     * Invoke a private method on an object.
     */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
