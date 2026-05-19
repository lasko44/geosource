<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citation_queries', function (Blueprint $table) {
            $table->string('visitor_id')->nullable()->after('user_id')->index();
        });

        Schema::table('citation_checks', function (Blueprint $table) {
            $table->string('visitor_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('citation_queries', function (Blueprint $table) {
            $table->dropColumn('visitor_id');
        });

        Schema::table('citation_checks', function (Blueprint $table) {
            $table->dropColumn('visitor_id');
        });
    }
};
