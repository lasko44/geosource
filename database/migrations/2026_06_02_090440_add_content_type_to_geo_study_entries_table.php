<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->string('content_type', 30)->nullable()->after('site_size')->index();
        });
    }

    public function down(): void
    {
        Schema::table('geo_study_entries', function (Blueprint $table) {
            $table->dropIndex(['content_type']);
            $table->dropColumn('content_type');
        });
    }
};
