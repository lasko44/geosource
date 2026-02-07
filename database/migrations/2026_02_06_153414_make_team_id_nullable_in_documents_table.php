<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes team_id nullable in documents table.
 *
 * Documents can belong to either a team OR a user:
 * - team_id set: Team document (for team scans)
 * - user_id set, team_id null: Personal document (for personal scans without team)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['team_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            // Make team_id nullable and re-add the foreign key
            $table->foreignId('team_id')
                ->nullable()
                ->change();

            // Re-add foreign key constraint
            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->nullable(false)
                ->change();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->cascadeOnDelete();
        });
    }
};
