<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Represents a document for RAG-based vector search.
 */
class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'content',
        'metadata',
    ];

    protected $hidden = [
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Set the embedding vector for this document.
     *
     * Note: Uses raw expression for pgvector type casting - Eloquent doesn't
     * natively support PostgreSQL vector types.
     *
     * @param  array<int, float>  $vector
     */
    public function setEmbedding(array $vector): void
    {
        $vectorString = '['.implode(',', $vector).']';

        // Raw expression required for pgvector type casting (::vector)
        self::where('id', $this->id)
            ->update(['embedding' => DB::raw("'$vectorString'::vector")]);
    }

    /**
     * Get the embedding vector for this document.
     *
     * Note: Uses selectRaw for pgvector type casting - Eloquent doesn't
     * natively support PostgreSQL vector types.
     *
     * @return array<int, float>|null
     */
    public function getEmbedding(): ?array
    {
        // Raw expression required for pgvector type casting (::text)
        $result = self::where('id', $this->id)
            ->selectRaw('embedding::text as embedding_text')
            ->first();

        if (! $result || ! $result->embedding_text) {
            return null;
        }

        $vector = trim($result->embedding_text, '[]');

        return array_map('floatval', explode(',', $vector));
    }

    /**
     * Search for similar documents using cosine similarity.
     *
     * Note: Uses raw expressions for pgvector cosine similarity operator (<=>)
     * which is not supported by Eloquent natively.
     *
     * @param  array<int, float>  $vector
     */
    public function scopeSimilarTo(Builder $query, array $vector, int $limit = 10): Builder
    {
        $vectorString = '['.implode(',', $vector).']';

        // Raw expressions required for pgvector cosine distance operator (<=>)
        return $query
            ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$vectorString])
            ->whereNotNull('embedding')
            ->orderByRaw('embedding <=> ?', [$vectorString])
            ->limit($limit);
    }

    /**
     * Search for similar documents within a team.
     *
     * Note: Uses raw expressions for pgvector cosine similarity operator (<=>)
     * which is not supported by Eloquent natively.
     *
     * @param  array<int, float>  $vector
     */
    public static function search(array $vector, int $teamId, int $limit = 10, float $threshold = 0.7): \Illuminate\Support\Collection
    {
        $vectorString = '['.implode(',', $vector).']';

        // Raw expressions required for pgvector cosine distance operator (<=>)
        return self::query()
            ->where('team_id', $teamId)
            ->whereNotNull('embedding')
            ->selectRaw('id, title, content, metadata, 1 - (embedding <=> ?) as similarity', [$vectorString])
            ->whereRaw('1 - (embedding <=> ?) >= ?', [$vectorString, $threshold])
            ->orderByRaw('embedding <=> ?', [$vectorString])
            ->limit($limit)
            ->get();
    }
}
