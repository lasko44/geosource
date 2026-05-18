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
                'description' => 'Test whether showing a free scan URL input on the homepage hero leads to more registrations vs the current CTA button.',
                'variants' => ['control', 'scan_input'],
                'status' => 'running',
                'started_at' => now(),
            ]
        );
    }
}
