<?php

namespace Database\Seeders;

use App\Models\Experiment;
use Illuminate\Database\Seeder;

/**
 * Seeds the homepage scan input A/B experiment.
 */
class HomepageExperimentSeeder extends Seeder
{
    public function run(): void
    {
        Experiment::updateOrCreate(
            ['name' => 'homepage_scan_input'],
            [
                'description' => 'Test free scan input vs free citation check on homepage hero. Both give guests a free taste of the product.',
                'variants' => ['scan_input', 'citation_check'],
                'status' => 'running',
                'started_at' => now(),
            ]
        );
    }
}
