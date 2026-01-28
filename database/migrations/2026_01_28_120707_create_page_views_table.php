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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->string('visitor_hash', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Page info
            $table->string('url', 2048);
            $table->string('path', 500)->index();
            $table->string('page_type', 50)->index();
            $table->unsignedBigInteger('page_id')->nullable()->index();
            $table->string('page_title', 500)->nullable();

            // Referrer
            $table->string('referrer', 2048)->nullable();
            $table->string('referrer_host', 255)->nullable()->index();

            // UTM parameters
            $table->string('utm_source', 255)->nullable()->index();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();

            // Geographic data (from IP)
            $table->string('country', 100)->nullable()->index();
            $table->string('country_code', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();

            // Device info
            $table->string('device_type', 20)->nullable()->index();
            $table->string('browser', 50)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('os_version', 20)->nullable();

            // Request info
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('is_bot')->default(false)->index();

            $table->timestamp('created_at')->useCurrent()->index();

            // Composite indexes for common queries
            $table->index(['page_type', 'created_at']);
            $table->index(['referrer_host', 'created_at']);
            $table->index(['country', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
