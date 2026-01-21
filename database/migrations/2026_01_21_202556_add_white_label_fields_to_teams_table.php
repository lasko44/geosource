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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('description');
            $table->string('logo_path')->nullable()->after('company_name');
            $table->string('primary_color', 7)->nullable()->after('logo_path');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
            $table->string('report_footer')->nullable()->after('secondary_color');
            $table->string('contact_email')->nullable()->after('report_footer');
            $table->string('website_url')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'logo_path',
                'primary_color',
                'secondary_color',
                'report_footer',
                'contact_email',
                'website_url',
            ]);
        });
    }
};
