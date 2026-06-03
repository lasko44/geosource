<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citation_queries', function (Blueprint $table) {
            // List of alternate brand display names users want to count
            // as a citation when they appear in AI responses without the
            // domain string. E.g. domain=hoka.com, brand_aliases=["Hoka", "Hoka One One"].
            // Built from v6 ecommerce research showing that AI shopping
            // responses regularly mention brand by name without including URLs.
            $table->json('brand_aliases')->nullable()->after('brand');
        });
    }

    public function down(): void
    {
        Schema::table('citation_queries', function (Blueprint $table) {
            $table->dropColumn('brand_aliases');
        });
    }
};
