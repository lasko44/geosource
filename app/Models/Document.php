<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
     * @param  array<int, float>  $vector
     */
    public function setEmbedding(array $vector): void
    {
        $vectorString = '['.implode(',', $vector).']';

        DB::statement(
            'UPDATE documents SET embedding = ? WHERE id = ?',
            [$vectorString, $this->id]
        );
    }

    /**
     * Get the embedding vector for this document.
     *
     * @return array<int, float>|null
     */
    public function getEmbedding(): ?array
    {
        $result = DB::selectOne(
            'SELECT embedding::text FROM documents WHERE id = ?',
            [$this->id]
        );

        if (! $result || ! $result->embedding) {
            return null;
        }

        $vector = trim($result->embedding, '[]');

        return array_map('floatval', explode(',', $vector));
    }

    /**
     * Search for similar documents using cosine similarity.
     *
     * @param  array<int, float>  $vector
     */
    public function scopeSimilarTo(Builder $query, array $vector, int $limit = 10): Builder
    {
        $vectorString = '['.implode(',', $vector).']';

        return $query
            ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$vectorString])
            ->whereNotNull('embedding')
            ->orderByRaw('embedding <=> ?', [$vectorString])
            ->limit($limit);
    }

    /**
     * Search for similar documents within a team.
     *
     * @param  array<int, float>  $vector
     */
    public static function search(array $vector, int $teamId, int $limit = 10, float $threshold = 0.7): \Illuminate\Support\Collection
    {
        $vectorString = '['.implode(',', $vector).']';

        return collect(DB::select('
            SELECT
                id,
                title,
                content,
                metadata,
                1 - (embedding <=> ?) as similarity
            FROM documents
            WHERE team_id = ?
              AND embedding IS NOT NULL
              AND 1 - (embedding <=> ?) >= ?
            ORDER BY embedding <=> ?
            LIMIT ?
        ', [$vectorString, $teamId, $vectorString, $threshold, $vectorString, $limit]));
    }
}
