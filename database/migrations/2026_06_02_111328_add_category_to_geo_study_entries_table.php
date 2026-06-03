<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('content_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
