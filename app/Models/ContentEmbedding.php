<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stores embeddings for public content pages (resources, programmatic SEO)
 * to power RAG-based suggested content recommendations.
 */
class ContentEmbedding extends Model
{
    protected $fillable = [
        'slug',
        'type',
        'title',
        'url',
        'excerpt',
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

    /**
     * Set the embedding vector for this content page.
     *
     * Note: Uses parameterized DB::statement for pgvector type casting.
     *
     * @param  array<int, float>  $vector
     */
    public function setEmbedding(array $vector): void
    {
        foreach ($vector as $value) {
            if (! is_numeric($value)) {
                throw new \InvalidArgumentException('Vector elements must be numeric');
            }
        }

        $vectorString = '['.implode(',', array_map('floatval', $vector)).']';

        // Raw expression required for pgvector type casting (::vector)
        DB::statement(
            'UPDATE content_embeddings SET embedding = ?::vector, updated_at = ? WHERE id = ?',
            [$vectorString, now(), $this->id]
        );
    }

    /**
     * Find content pages similar to the given vector, excluding a specific slug.
     *
     * Note: Uses raw expressions for pgvector cosine distance operator (<=>).
     *
     * @param  array<int, float>  $vector
     */
    public static function findSimilar(array $vector, string $excludeSlug, int $limit = 4, float $threshold = 0.3): Collection
    {
        $vectorString = '['.implode(',', array_map('floatval', $vector)).']';

        // Raw expressions required for pgvector cosine distance operator (<=>)
        return self::query()
            ->where('slug', '!=', $excludeSlug)
            ->whereNotNull('embedding')
            ->selectRaw('slug, type, title, url, excerpt, metadata, 1 - (embedding <=> ?) as similarity', [$vectorString])
            ->whereRaw('1 - (embedding <=> ?) >= ?', [$vectorString, $threshold])
            ->orderByRaw('embedding <=> ?', [$vectorString])
            ->limit($limit)
            ->get();
    }

    /**
     * Get the embedding vector for this content page.
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
     * Scope to filter by content type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
