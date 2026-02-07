<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Represents a document for RAG-based vector search.
 *
 * Documents can belong to either a team OR a user for isolation:
 * - team_id set: Team document (shared within team)
 * - user_id set: Personal document (private to user)
 */
class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the embedding vector for this document.
     *
     * Note: Uses parameterized DB::statement for pgvector type casting - Eloquent
     * doesn't natively support PostgreSQL vector types. Input is validated and
     * parameterized to prevent SQL injection.
     *
     * @param  array<int, float>  $vector
     */
    public function setEmbedding(array $vector): void
    {
        // Validate all elements are numeric to prevent SQL injection
        foreach ($vector as $value) {
            if (! is_numeric($value)) {
                throw new \InvalidArgumentException('Vector elements must be numeric');
            }
        }

        // Cast all values to float for safety, then build vector string
        $vectorString = '['.implode(',', array_map('floatval', $vector)).']';

        // Use parameterized query to prevent SQL injection
        // Raw expression required for pgvector type casting (::vector)
        DB::statement(
            'UPDATE documents SET embedding = ?::vector, updated_at = ? WHERE id = ?',
            [$vectorString, now(), $this->id]
        );
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
