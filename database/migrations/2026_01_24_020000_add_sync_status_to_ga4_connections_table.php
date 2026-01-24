<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga4_connections', function (Blueprint $table) {
            $table->string('sync_status')->default('idle')->after('last_synced_at');
            $table->text('sync_error')->nullable()->after('sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('ga4_connections', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'sync_error']);
        });
    }
};
