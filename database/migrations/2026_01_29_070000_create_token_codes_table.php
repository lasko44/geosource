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
        Schema::create('token_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20)->default('promo'); // 'promo' (multi-use) or 'single' (one-time)
            $table->string('description')->nullable();
            $table->integer('tokens'); // How many tokens this code gives
            $table->integer('max_uses')->nullable(); // Null = unlimited uses (for promo), 1 for single-use
            $table->integer('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index('expires_at');
        });

        Schema::create('token_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_code_id')->constrained('token_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('tokens_received');
            $table->timestamps();

            // Ensure each user can only redeem a code once
            $table->unique(['token_code_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_code_redemptions');
        Schema::dropIfExists('token_codes');
    }
};
