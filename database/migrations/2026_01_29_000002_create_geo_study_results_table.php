<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_study_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('geo_study_id')->constrained('geo_studies')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('title')->nullable();
            $table->string('domain')->nullable();
            $table->string('category')->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->string('grade', 2)->nullable(); // A+, A, B+, B, C+, C, D, F
            $table->decimal('percentage', 5, 2)->nullable();

            // 12 pillar score columns (denormalized for fast querying)
            $table->decimal('pillar_definitions', 5, 2)->nullable();
            $table->decimal('pillar_structure', 5, 2)->nullable();
            $table->decimal('pillar_authority', 5, 2)->nullable();
            $table->decimal('pillar_machine_readable', 5, 2)->nullable();
            $table->decimal('pillar_answerability', 5, 2)->nullable();
            $table->decimal('pillar_eeat', 5, 2)->nullable();
            $table->decimal('pillar_citations', 5, 2)->nullable();
            $table->decimal('pillar_ai_accessibility', 5, 2)->nullable();
            $table->decimal('pillar_freshness', 5, 2)->nullable();
            $table->decimal('pillar_readability', 5, 2)->nullable();
            $table->decimal('pillar_question_coverage', 5, 2)->nullable();
            $table->decimal('pillar_multimedia', 5, 2)->nullable();

            $table->json('full_results')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->string('source_type')->nullable(); // csv, serp, sitemap
            $table->json('source_metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['geo_study_id', 'status']);
            $table->index(['geo_study_id', 'grade']);
            $table->index(['geo_study_id', 'total_score']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_study_results');
    }
};
