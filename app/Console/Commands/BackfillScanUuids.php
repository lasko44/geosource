<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillScanUuids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scans:backfill-uuids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill UUIDs for scans that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = DB::table('scans')->whereNull('uuid')->count();

        if ($count === 0) {
            $this->info('All scans already have UUIDs.');

            return Command::SUCCESS;
        }

        $this->info("Found {$count} scans without UUIDs. Backfilling...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table('scans')->whereNull('uuid')->orderBy('id')->chunk(100, function ($scans) use ($bar) {
            foreach ($scans as $scan) {
                DB::table('scans')->where('id', $scan->id)->update([
                    'uuid' => Str::uuid()->toString(),
                ]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully backfilled UUIDs for {$count} scans.");

        return Command::SUCCESS;
    }
}
