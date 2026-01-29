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
        Schema::table('scans', function (Blueprint $table) {
            // The tier requested by the user (basic, pro, full)
            // Defaults to 'basic' for free scans
            $table->string('requested_tier', 20)->default('basic')->after('team_id');

            // Whether tokens were charged for this scan
            $table->boolean('tokens_charged')->default(false)->after('requested_tier');

            // How many tokens were charged (0 for free/subscription scans)
            $table->integer('tokens_amount')->default(0)->after('tokens_charged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['requested_tier', 'tokens_charged', 'tokens_amount']);
        });
    }
};
