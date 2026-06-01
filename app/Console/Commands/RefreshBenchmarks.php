<?php

namespace App\Console\Commands;

use App\Services\Analytics\CorrelationService;
use Illuminate\Console\Command;

/**
 * Refreshes industry benchmarks from accumulated correlation data.
 */
class RefreshBenchmarks extends Command
{
    protected $signature = 'benchmarks:refresh';

    protected $description = 'Refresh industry benchmarks from correlation data';

    public function handle(CorrelationService $correlationService): int
    {
        $updated = $correlationService->refreshBenchmarks();
        $this->info("Refreshed {$updated} industry benchmarks.");

        return Command::SUCCESS;
    }
}
