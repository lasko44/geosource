<?php

namespace App\Console\Commands;

use App\Models\PageView;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPageViewEngagement extends Command
{
    protected $signature = 'pageviews:backfill-engagement';

    protected $description = 'Backfill engaged_at for existing page views';

    public function handle(): int
    {
        $count = PageView::whereNull('engaged_at')->count();

        if ($count === 0) {
            $this->info('No page views need backfilling.');

            return self::SUCCESS;
        }

        $this->info("Backfilling {$count} page views...");

        PageView::whereNull('engaged_at')->update([
            'engaged_at' => DB::raw('created_at'),
        ]);

        $this->info('Done! All existing page views marked as engaged.');

        return self::SUCCESS;
    }
}
