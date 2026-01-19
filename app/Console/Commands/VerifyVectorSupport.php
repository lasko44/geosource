<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyVectorSupport extends Command
{
    protected $signature = 'vector:verify';

    protected $description = 'Verify pgvector extension is working with inserts and queries';

    public function handle(): int
    {
        $this->info('Verifying pgvector support...');

        // Check if extension is installed
        $this->info('1. Checking pgvector extension...');
        $extension = DB::selectOne("SELECT * FROM pg_extension WHERE extname = 'vector'");

        if (! $extension) {
            $this->error('pgvector extension is not installed!');

            return Command::FAILURE;
        }
        $this->info('   Extension installed.');

        // Check if test table exists
        $this->info('2. Checking vector_test table...');
        $tableExists = DB::selectOne("SELECT to_regclass('vector_test') IS NOT NULL as exists");

        if (! $tableExists->exists) {
            $this->error('vector_test table does not exist. Run migrations first.');

            return Command::FAILURE;
        }
        $this->info('   Table exists.');

        // Clear any existing test data
        DB::table('vector_test')->truncate();

        // Test INSERT
        $this->info('3. Testing vector INSERT...');
        DB::statement("INSERT INTO vector_test (name, embedding, created_at, updated_at) VALUES ('test1', '[1,2,3]', NOW(), NOW())");
        DB::statement("INSERT INTO vector_test (name, embedding, created_at, updated_at) VALUES ('test2', '[4,5,6]', NOW(), NOW())");
        DB::statement("INSERT INTO vector_test (name, embedding, created_at, updated_at) VALUES ('test3', '[1,2,4]', NOW(), NOW())");
        $this->info('   Inserted 3 test vectors.');

        // Test SELECT
        $this->info('4. Testing vector SELECT...');
        $results = DB::select('SELECT name, embedding::text FROM vector_test');
        foreach ($results as $row) {
            $this->line("   - {$row->name}: {$row->embedding}");
        }

        // Test cosine similarity search (L2 distance)
        $this->info('5. Testing similarity search (L2 distance)...');
        $searchVector = '[1,2,3]';
        $similar = DB::select("
            SELECT name, embedding::text, embedding <-> '{$searchVector}' as distance
            FROM vector_test
            ORDER BY distance
            LIMIT 3
        ");

        $this->table(['Name', 'Embedding', 'Distance'], array_map(function ($row) {
            return [$row->name, $row->embedding, round($row->distance, 4)];
        }, $similar));

        // Test cosine similarity
        $this->info('6. Testing cosine similarity...');
        $cosineSimilar = DB::select("
            SELECT name, embedding::text, 1 - (embedding <=> '{$searchVector}') as similarity
            FROM vector_test
            ORDER BY similarity DESC
            LIMIT 3
        ");

        $this->table(['Name', 'Embedding', 'Cosine Similarity'], array_map(function ($row) {
            return [$row->name, $row->embedding, round($row->similarity, 4)];
        }, $cosineSimilar));

        // Cleanup
        DB::table('vector_test')->truncate();

        $this->newLine();
        $this->info('All vector operations verified successfully!');

        return Command::SUCCESS;
    }
}
