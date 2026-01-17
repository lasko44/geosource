<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vector_test', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE vector_test ADD COLUMN embedding vector(3)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vector_test');
    }
};
