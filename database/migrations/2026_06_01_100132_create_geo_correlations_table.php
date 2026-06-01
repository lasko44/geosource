<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_correlations', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->index();
            $table->string('url', 2048);

            // Scan data
            $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('geo_score')->nullable();
            $table->decimal('geo_percentage', 5, 1)->nullable();
            $table->string('geo_grade', 5)->nullable();
            $table->decimal('citation_readiness_score', 5, 1)->nullable();
            $table->string('content_type', 30)->nullable();
            $table->json('pillar_scores')->nullable();

            // Citation data
            $table->foreignId('citation_query_id')->nullable()->constrained()->nullOnDelete();
            $table->string('query', 500)->nullable();
            $table->integer('platforms_checked')->default(0);
            $table->integer('platforms_cited')->default(0);
            $table->decimal('citation_rate', 5, 2)->nullable();
            $table->json('platforms_cited_list')->nullable();

            // Context
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_id', 100)->nullable()->index();
            $table->string('industry', 100)->nullable()->index();
            $table->string('source', 30)->default('user')->index();

            $table->timestamps();

            $table->index(['domain', 'created_at']);
            $table->index(['geo_score', 'citation_rate']);
            $table->index(['content_type', 'citation_rate']);
            $table->index(['industry', 'geo_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_correlations');
    }
};
