<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->string('study_version', 50)->default('v1')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->dropColumn('study_version');
        });
    }
};
