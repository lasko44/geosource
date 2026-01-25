<?php

namespace App\Console\Commands;

use App\Services\BlogPostGeoService;
use Illuminate\Console\Command;

class RegenerateBlogGeoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:regenerate-geo
                            {--schema : Only regenerate schema.org JSON for all posts}
                            {--llms : Only regenerate llms.txt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate GEO data (schema.org JSON and llms.txt) for all blog posts';

    public function __construct(
        private BlogPostGeoService $geoService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schemaOnly = $this->option('schema');
        $llmsOnly = $this->option('llms');

        // If no specific option, do both
        $doBoth = ! $schemaOnly && ! $llmsOnly;

        if ($doBoth || $schemaOnly) {
            $this->info('Regenerating schema.org JSON for all blog posts...');
            $count = $this->geoService->regenerateAllSchemas();
            $this->info("Generated schema for {$count} blog posts.");
        }

        if ($doBoth || $llmsOnly) {
            $this->info('Regenerating llms.txt...');
            $this->geoService->updateLlmsTxt();
            $this->info('llms.txt updated successfully.');
        }

        $this->newLine();
        $this->info('GEO data regeneration complete.');

        return Command::SUCCESS;
    }
}
