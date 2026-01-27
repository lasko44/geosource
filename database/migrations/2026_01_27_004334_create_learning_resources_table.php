<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category'); // Foundation, Technical, Comparison, etc.
            $table->string('category_icon')->default('BookOpen'); // Lucide icon name
            $table->text('excerpt'); // Short description shown on index
            $table->text('intro')->nullable(); // Intro paragraph below title

            // Main content as HTML (editable with rich text editor)
            $table->longText('content');

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();

            // JSON-LD structured data (stored as JSON for flexibility)
            $table->json('json_ld')->nullable();
            $table->json('faq_json_ld')->nullable();

            // Navigation
            $table->string('prev_slug')->nullable();
            $table->string('prev_title')->nullable();
            $table->string('next_slug')->nullable();
            $table->string('next_title')->nullable();

            // Related articles (array of slugs)
            $table->json('related_articles')->nullable();

            // Display settings
            $table->boolean('is_featured')->default(false); // Show in quick-access cards
            $table->string('featured_icon')->nullable(); // Icon for featured card
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_resources');
    }
};
