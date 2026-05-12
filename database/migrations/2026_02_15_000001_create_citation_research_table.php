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
        Schema::create('citation_research', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('topic', 500);
            $table->string('tier')->default('research'); // research, research_audit
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('progress_step')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->json('platforms_queried')->nullable();
            $table->json('platform_responses')->nullable();
            $table->json('sources_found')->nullable();
            $table->json('analysis_results')->nullable();
            $table->text('report')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('tokens_charged')->default(false);
            $table->unsignedInteger('tokens_amount')->nullable();
            $table->boolean('tokens_refunded')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index('created_at');
        });

        // Add citation_research_id foreign key to scans table for audit tier
        Schema::table('scans', function (Blueprint $table) {
            $table->foreignId('citation_research_id')
                ->nullable()
                ->after('scheduled_scan_id')
                ->constrained('citation_research')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['citation_research_id']);
            $table->dropColumn('citation_research_id');
        });

        Schema::dropIfExists('citation_research');
    }
};
