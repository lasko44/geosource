<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->index(); // resource, industry, platform, comparison
            $table->string('title');
            $table->string('url');
            $table->text('excerpt');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // Add pgvector embedding column - raw SQL required for vector type
        DB::statement('ALTER TABLE content_embeddings ADD COLUMN embedding vector(1536)');

        // IVFFlat index for fast cosine similarity search
        DB::statement('CREATE INDEX content_embeddings_embedding_idx ON content_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 50)');
    }

    public function down(): void
    {
        Schema::dropIfExists('content_embeddings');
    }
};
