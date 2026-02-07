<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds competitor discovery status tracking to scans table.
 *
 * Tracks the progress of AI competitor discovery:
 * - pending: Waiting to start discovery
 * - discovering: AI is finding competitors
 * - scanning: Competitor scans are in progress
 * - completed: All competitor scans finished
 * - failed: Discovery failed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->string('competitor_discovery_status')->nullable()->after('auto_find_competitors');
            $table->unsignedSmallInteger('competitors_found')->default(0)->after('competitor_discovery_status');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['competitor_discovery_status', 'competitors_found']);
        });
    }
};
