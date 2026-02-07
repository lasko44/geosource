<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds is_competitor flag to scans table for competitive benchmarking.
 *
 * Competitor scans are used for comparison against user's own content
 * but are not included in "own content" calculations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->boolean('is_competitor')
                ->default(false)
                ->after('requested_tier')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex(['is_competitor']);
            $table->dropColumn('is_competitor');
        });
    }
};
