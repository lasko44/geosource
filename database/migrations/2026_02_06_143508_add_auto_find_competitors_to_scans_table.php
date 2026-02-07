<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds auto_find_competitors flag to scans table.
 *
 * When enabled on full tier scans, the system will use AI to discover
 * and automatically scan competitor pages for benchmarking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->boolean('auto_find_competitors')
                ->default(false)
                ->after('is_competitor');

            // Track the parent scan that triggered competitor discovery
            $table->foreignId('parent_scan_id')
                ->nullable()
                ->after('auto_find_competitors')
                ->constrained('scans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['parent_scan_id']);
            $table->dropColumn(['auto_find_competitors', 'parent_scan_id']);
        });
    }
};
