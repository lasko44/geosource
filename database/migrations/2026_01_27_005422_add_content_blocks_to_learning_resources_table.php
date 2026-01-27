<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            // Content type: 'html' (simple), 'blocks' (structured JSON blocks)
            $table->string('content_type')->default('html')->after('intro');

            // JSON blocks for structured content
            // Each block has: type, props, content
            $table->json('content_blocks')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropColumn(['content_type', 'content_blocks']);
        });
    }
};
