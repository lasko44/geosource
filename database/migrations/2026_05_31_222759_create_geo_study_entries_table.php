<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_study_entries', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->string('domain', 255)->index();
            $table->string('industry', 100)->index();
            $table->string('site_size', 20)->index();
            $table->string('query', 500);

            // Scan results
            $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('geo_score')->nullable();
            $table->string('geo_grade', 5)->nullable();
            $table->json('pillar_scores')->nullable();

            // Citation results
            $table->foreignId('citation_query_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('citations_checked')->default(0);
            $table->integer('citations_cited')->default(0);
            $table->decimal('citation_rate', 5, 2)->nullable();
            $table->json('platforms_cited')->nullable();

            // Status tracking
            $table->string('status', 30)->default('pending')->index();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['geo_score', 'citation_rate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_study_entries');
    }
};
