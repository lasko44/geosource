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
        Schema::create('ga4_referral_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga4_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source'); // e.g., perplexity.ai, chat.openai.com
            $table->string('medium')->nullable(); // e.g., referral
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('users')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedInteger('engaged_sessions')->default(0);
            $table->decimal('bounce_rate', 5, 2)->nullable();
            $table->decimal('avg_session_duration', 10, 2)->nullable(); // in seconds
            $table->timestamps();

            $table->unique(['ga4_connection_id', 'date', 'source']);
            $table->index(['team_id', 'date']);
            $table->index(['source', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga4_referral_data');
    }
};
