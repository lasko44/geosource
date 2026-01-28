<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_studies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('mixed'); // news, ecommerce, saas, blog, mixed
            $table->string('source_type')->default('csv'); // csv, serp, sitemap
            $table->json('source_config')->nullable();
            $table->string('status')->default('draft'); // draft, collecting, processing, completed, failed, cancelled
            $table->unsignedInteger('total_urls')->default(0);
            $table->unsignedInteger('processed_urls')->default(0);
            $table->unsignedInteger('failed_urls')->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('batch_id')->nullable(); // Laravel batch tracking
            $table->json('aggregate_stats')->nullable();
            $table->json('category_breakdown')->nullable();
            $table->json('pillar_analysis')->nullable();
            $table->json('top_performers')->nullable();
            $table->json('bottom_performers')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['created_by', 'status']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_studies');
    }
};
