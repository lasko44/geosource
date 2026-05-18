<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiment_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['experiment_id', 'visitor_id']);
            $table->index(['experiment_id', 'variant', 'converted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_participants');
    }
};
