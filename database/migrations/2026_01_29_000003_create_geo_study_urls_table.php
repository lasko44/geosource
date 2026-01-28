<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_study_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_study_id')->constrained('geo_studies')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('source_type')->nullable(); // csv, serp, sitemap
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending'); // pending, queued, processed, skipped
            $table->timestamps();

            $table->index(['geo_study_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_study_urls');
    }
};
