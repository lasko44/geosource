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
        Schema::create('scheduled_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('name')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->time('scheduled_time')->default('09:00:00');
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sunday, 6=Saturday (for weekly)
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-28 (for monthly)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->unsignedInteger('total_runs')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index(['team_id', 'is_active']);
            $table->index(['next_run_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_scans');
    }
};
