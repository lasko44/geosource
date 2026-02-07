<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds user_id column to documents table for personal scan isolation.
 *
 * Documents can now belong to either a team OR a user:
 * - team_id set: Team document (for team scans)
 * - user_id set: Personal document (for personal scans without team)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('team_id')
                ->constrained()
                ->cascadeOnDelete();

            // Index for user-based queries
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
