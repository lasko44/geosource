<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('industry', 100)->unique();
            $table->integer('sample_size')->default(0);
            $table->decimal('avg_geo_score', 5, 1)->nullable();
            $table->decimal('avg_citation_rate', 5, 2)->nullable();
            $table->decimal('avg_citation_readiness', 5, 1)->nullable();
            $table->string('dominant_content_type', 30)->nullable();
            $table->decimal('p25_score', 5, 1)->nullable();
            $table->decimal('p50_score', 5, 1)->nullable();
            $table->decimal('p75_score', 5, 1)->nullable();
            $table->json('pillar_averages')->nullable();
            $table->string('data_source', 30)->default('study');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_benchmarks');
    }
};
