<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citation_checks', function (Blueprint $table) {
            // Composite 0-100 strength score: how strongly was the brand
            // recommended in this AI response. Null until analyzed.
            $table->decimal('recommendation_strength', 5, 2)->nullable()->after('citations');
            // Structured breakdown: {mention_type, position_rank, is_top_pick,
            // top_pick_rank, has_buy_link, reasoning}. Lets us iterate on the
            // composite formula without re-running expensive LLM calls.
            $table->json('mention_analysis')->nullable()->after('recommendation_strength');
        });
    }

    public function down(): void
    {
        Schema::table('citation_checks', function (Blueprint $table) {
            $table->dropColumn(['recommendation_strength', 'mention_analysis']);
        });
    }
};
