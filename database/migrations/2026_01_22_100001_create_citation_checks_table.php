<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('citation_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('citation_query_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('platform'); // perplexity, openai, claude, gemini
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('progress_step')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->boolean('is_cited')->nullable(); // Whether domain/brand was cited
            $table->text('ai_response')->nullable(); // Full AI response
            $table->json('citations')->nullable(); // Extracted citation details
            $table->json('metadata')->nullable(); // Additional context (model used, tokens, etc.)
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['citation_query_id', 'platform', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['team_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['platform', 'is_cited']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citation_checks');
    }
};
