<?php

namespace App\Services\RAG;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Vector store for managing document embeddings with pgvector.
 *
 * Provides methods for:
 * - Storing documents with embeddings
 * - Similarity search
 * - Hybrid search (semantic + keyword)
 * - Metadata filtering
 */
class VectorStore
{
    public function __construct(
        private EmbeddingService $embeddingService,
        private ChunkingService $chunkingService,
    ) {}

    /**
     * Add a document to the vector store.
     */
    public function addDocument(
        int $teamId,
        string $title,
        string $content,
        array $metadata = [],
        bool $chunk = true
    ): array {
        $documents = [];

        if ($chunk) {
            // Chunk the content
            $chunks = $this->chunkingService->chunk($content, [
                'source_title' => $title,
                'source_type' => $metadata['type'] ?? 'document',
            ]);

            // Also create a summary chunk for hierarchical retrieval
            $summaryChunk = $this->chunkingService->createSummaryChunk($content, [
                'source_title' => $title,
                'source_type' => $metadata['type'] ?? 'document',
            ]);
            array_unshift($chunks, $summaryChunk);

            // Generate embeddings in batch
            $texts = array_column($chunks, 'content');
            $embeddings = $this->embeddingService->embedBatch($texts);

            // Store each chunk as a document
            foreach ($chunks as $index => $chunk) {
                $doc = Document::create([
                    'team_id' => $teamId,
                    'title' => $chunk['metadata']['is_summary'] ?? false
                        ? "[Summary] {$title}"
                        : "{$title} (chunk {$index})",
                    'content' => $chunk['content'],
                    'metadata' => array_merge($metadata, $chunk['metadata'], [
                        'parent_title' => $title,
                        'total_chunks' => count($chunks),
                    ]),
                ]);

                $doc->setEmbedding($embeddings[$index]);
                $documents[] = $doc;
            }
        } else {
            // Store as single document
            $embedding = $this->embeddingService->embed($content);

            $doc = Document::create([
                'team_id' => $teamId,
                'title' => $title,
                'content' => $content,
                'metadata' => $metadata,
            ]);

            $doc->setEmbedding($embedding);
            $documents[] = $doc;
        }

        return $documents;
    }

    /**
     * Search for similar documents using semantic search.
     */
    public function search(
        string $query,
        int $teamId,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = []
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($query);

        return $this->searchByVector($queryEmbedding, $teamId, $limit, $threshold, $filters);
    }

    /**
     * Search by vector directly.
     */
    public function searchByVector(
        array $vector,
        int $teamId,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = []
    ): Collection {
        $vectorString = '['.implode(',', $vector).']';

        $query = DB::table('documents')
            ->select([
                'id',
                'title',
                'content',
                'metadata',
                'created_at',
            ])
            ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->where('team_id', $teamId)
            ->whereNotNull('embedding')
            ->whereRaw('1 - (embedding <=> ?::vector) >= ?', [$vectorString, $threshold]);

        // Apply metadata filters
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $query->whereRaw("metadata->>'$key' = ANY(?)", ['{'.implode(',', $value).'}']);
            } else {
                $query->whereRaw("metadata->>'$key' = ?", [$value]);
            }
        }

        return $query
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit($limit)
            ->get()
            ->map(function ($doc) {
                $doc->metadata = json_decode($doc->metadata, true);

                return $doc;
            });
    }

    /**
     * Hybrid search combining semantic and keyword search.
     */
    public function hybridSearch(
        string $query,
        int $teamId,
        int $limit = 10,
        float $semanticWeight = 0.7,
        array $filters = []
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($query);
        $vectorString = '['.implode(',', $queryEmbedding).']';

        // Normalize the query for text search
        $searchTerms = $this->prepareSearchTerms($query);

        $results = DB::table('documents')
            ->select([
                'id',
                'title',
                'content',
                'metadata',
                'created_at',
            ])
            ->selectRaw('1 - (embedding <=> ?::vector) as semantic_score', [$vectorString])
            ->selectRaw(
                'ts_rank(to_tsvector(\'english\', content), plainto_tsquery(\'english\', ?)) as keyword_score',
                [$searchTerms]
            )
            ->selectRaw(
                '(? * (1 - (embedding <=> ?::vector))) + (? * ts_rank(to_tsvector(\'english\', content), plainto_tsquery(\'english\', ?))) as combined_score',
                [$semanticWeight, $vectorString, 1 - $semanticWeight, $searchTerms]
            )
            ->where('team_id', $teamId)
            ->whereNotNull('embedding');

        // Apply metadata filters
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $results->whereRaw("metadata->>'$key' = ANY(?)", ['{'.implode(',', $value).'}']);
            } else {
                $results->whereRaw("metadata->>'$key' = ?", [$value]);
            }
        }

        return $results
            ->orderByDesc('combined_score')
            ->limit($limit)
            ->get()
            ->map(function ($doc) {
                $doc->metadata = json_decode($doc->metadata, true);
                $doc->similarity = $doc->combined_score;

                return $doc;
            });
    }

    /**
     * Find documents similar to an existing document.
     */
    public function findSimilar(
        int $documentId,
        int $limit = 5,
        float $threshold = 0.6
    ): Collection {
        $document = Document::findOrFail($documentId);
        $embedding = $document->getEmbedding();

        if (! $embedding) {
            return collect();
        }

        $vectorString = '['.implode(',', $embedding).']';

        return DB::table('documents')
            ->select(['id', 'title', 'content', 'metadata'])
            ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->where('team_id', $document->team_id)
            ->where('id', '!=', $documentId)
            ->whereNotNull('embedding')
            ->whereRaw('1 - (embedding <=> ?::vector) >= ?', [$vectorString, $threshold])
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit($limit)
            ->get()
            ->map(function ($doc) {
                $doc->metadata = json_decode($doc->metadata, true);

                return $doc;
            });
    }

    /**
     * Get documents by metadata filter.
     */
    public function getByMetadata(int $teamId, array $filters, int $limit = 100): Collection
    {
        $query = Document::where('team_id', $teamId);

        foreach ($filters as $key => $value) {
            $query->whereRaw("metadata->>'$key' = ?", [$value]);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Update document embedding.
     */
    public function updateEmbedding(Document $document): void
    {
        $embedding = $this->embeddingService->embed($document->content);
        $document->setEmbedding($embedding);
    }

    /**
     * Delete documents by metadata filter.
     */
    public function deleteByMetadata(int $teamId, array $filters): int
    {
        $query = Document::where('team_id', $teamId);

        foreach ($filters as $key => $value) {
            $query->whereRaw("metadata->>'$key' = ?", [$value]);
        }

        return $query->delete();
    }

    /**
     * Get cluster of semantically similar documents.
     */
    public function getCluster(
        int $teamId,
        int $centroidDocumentId,
        float $threshold = 0.7,
        int $limit = 50
    ): Collection {
        $centroid = Document::findOrFail($centroidDocumentId);
        $embedding = $centroid->getEmbedding();

        if (! $embedding) {
            return collect([$centroid]);
        }

        return $this->searchByVector($embedding, $teamId, $limit, $threshold)
            ->prepend((object) [
                'id' => $centroid->id,
                'title' => $centroid->title,
                'content' => $centroid->content,
                'metadata' => $centroid->metadata,
                'similarity' => 1.0,
            ]);
    }

    /**
     * Calculate average similarity between documents.
     */
    public function calculateTopicCoherence(int $teamId, array $documentIds): float
    {
        if (count($documentIds) < 2) {
            return 1.0;
        }

        $documents = Document::whereIn('id', $documentIds)->get();
        $embeddings = [];

        foreach ($documents as $doc) {
            $embedding = $doc->getEmbedding();
            if ($embedding) {
                $embeddings[$doc->id] = $embedding;
            }
        }

        if (count($embeddings) < 2) {
            return 0.0;
        }

        $totalSimilarity = 0;
        $comparisons = 0;

        $ids = array_keys($embeddings);
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $similarity = $this->embeddingService->cosineSimilarity(
                    $embeddings[$ids[$i]],
                    $embeddings[$ids[$j]]
                );
                $totalSimilarity += $similarity;
                $comparisons++;
            }
        }

        return $comparisons > 0 ? $totalSimilarity / $comparisons : 0.0;
    }

    /**
     * Prepare search terms for full-text search.
     */
    private function prepareSearchTerms(string $query): string
    {
        // Remove special characters and normalize
        $terms = preg_replace('/[^\w\s]/', ' ', $query);
        $terms = preg_replace('/\s+/', ' ', $terms);

        return trim($terms);
    }
}
