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
        Schema::table('page_views', function (Blueprint $table) {
            // Track when a visitor engaged (stayed on page for X seconds)
            $table->timestamp('engaged_at')->nullable()->after('is_bot');
            $table->index(['engaged_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex(['engaged_at', 'created_at']);
            $table->dropColumn('engaged_at');
        });
    }
};
