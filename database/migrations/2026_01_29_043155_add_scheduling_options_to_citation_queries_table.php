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
        Schema::table('citation_queries', function (Blueprint $table) {
            // Platforms to run on scheduled checks (JSON array)
            $table->json('scheduled_platforms')->nullable()->after('frequency');

            // Monthly token budget for this query (null = unlimited)
            $table->integer('monthly_token_budget')->nullable()->after('scheduled_platforms');

            // Track tokens spent this month
            $table->integer('tokens_spent_this_month')->default(0)->after('monthly_token_budget');

            // When the monthly counter was last reset
            $table->timestamp('budget_reset_at')->nullable()->after('tokens_spent_this_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citation_queries', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_platforms',
                'monthly_token_budget',
                'tokens_spent_this_month',
                'budget_reset_at',
            ]);
        });
    }
};
