<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique constraint on the token column to prevent token collision attacks.
     * This ensures that each invitation has a cryptographically unique token.
     */
    public function up(): void
    {
        // Check if the unique constraint already exists (PostgreSQL)
        $constraintExists = DB::select("
            SELECT 1 FROM pg_constraint
            WHERE conname = 'team_invitations_token_unique'
        ");

        if (empty($constraintExists)) {
            Schema::table('team_invitations', function (Blueprint $table) {
                $table->unique('token', 'team_invitations_token_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the constraint exists before trying to drop it
        $constraintExists = DB::select("
            SELECT 1 FROM pg_constraint
            WHERE conname = 'team_invitations_token_unique'
        ");

        if (! empty($constraintExists)) {
            Schema::table('team_invitations', function (Blueprint $table) {
                $table->dropUnique('team_invitations_token_unique');
            });
        }
    }
};
