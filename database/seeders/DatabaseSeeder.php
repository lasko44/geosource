<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user first
        $this->call(AdminUserSeeder::class);

        // Seed token packages
        $this->call(TokenPackageSeeder::class);

        // Seed email templates
        $this->call(TokenAnnouncementEmailSeeder::class);

        // Seed comprehensive test data (users, scans, citations, teams, etc.)
        $this->call(TestDataSeeder::class);

        // Import blog posts
        $this->command->info('Importing blog posts...');
        Artisan::call('blog:import');
        $this->command->info(Artisan::output());
    }
}
