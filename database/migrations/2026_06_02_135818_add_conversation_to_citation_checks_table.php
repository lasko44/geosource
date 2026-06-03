<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citation_checks', function (Blueprint $table) {
            // Groups multi-turn conversation checks. All turns in a single
            // brand+platform conversation share the same conversation_id.
            $table->string('conversation_id', 36)->nullable()->after('platform')->index();
            // Position within the conversation, 1-indexed (1 = first turn).
            $table->unsignedTinyInteger('turn_index')->nullable()->after('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('citation_checks', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropColumn(['conversation_id', 'turn_index']);
        });
    }
};
