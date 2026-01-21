<?php

namespace App\Services\RAG;

use App\Models\Document;
use App\Models\User;
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
 *
 * Security: All operations require team_id and optionally accept a User
 * for authorization validation. When a User is provided, access is verified
 * before any operation.
 */
class VectorStore
{
    /**
     * Maximum allowed limit for search operations to prevent resource exhaustion.
     */
    private const MAX_SEARCH_LIMIT = 100;

    public function __construct(
        private EmbeddingService $embeddingService,
        private ChunkingService $chunkingService,
    ) {}

    /**
     * Validate that a user has access to a team.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeTeamAccess(int $teamId, ?User $user): void
    {
        if ($user === null) {
            return; // No user provided, skip authorization (internal use)
        }

        // Admins have access to all teams
        if ($user->is_admin) {
            return;
        }

        // Check if user belongs to the team
        if (! $user->allTeams()->contains('id', $teamId)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You do not have access to this team\'s documents.'
            );
        }
    }

    /**
     * Enforce maximum search limit to prevent resource exhaustion.
     */
    private function enforceSearchLimit(int $limit): int
    {
        return min($limit, self::MAX_SEARCH_LIMIT);
    }

    /**
     * Add a document to the vector store.
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function addDocument(
        int $teamId,
        string $title,
        string $content,
        array $metadata = [],
        bool $chunk = true,
        ?User $user = null
    ): array {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

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

            // Generate embeddings in batch (with team isolation for cache)
            $texts = array_column($chunks, 'content');
            $embeddings = $this->embeddingService->embedBatch($texts, true, $teamId);

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
            // Store as single document (with team isolation for cache)
            $embedding = $this->embeddingService->embed($content, true, $teamId);

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
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function search(
        string $query,
        int $teamId,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = [],
        ?User $user = null
    ): Collection {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        // Pass teamId for cache isolation
        $queryEmbedding = $this->embeddingService->embed($query, true, $teamId);

        return $this->searchByVector($queryEmbedding, $teamId, $limit, $threshold, $filters);
    }

    /**
     * Search by vector directly.
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function searchByVector(
        array $vector,
        int $teamId,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = [],
        ?User $user = null
    ): Collection {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

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

        // Apply metadata filters with validation
        $this->applyMetadataFilters($query, $filters);

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
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function hybridSearch(
        string $query,
        int $teamId,
        int $limit = 10,
        float $semanticWeight = 0.7,
        array $filters = [],
        ?User $user = null
    ): Collection {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        // Pass teamId for cache isolation
        $queryEmbedding = $this->embeddingService->embed($query, true, $teamId);
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

        // Apply metadata filters with validation
        $this->applyMetadataFilters($results, $filters);

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
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to document's team is verified.
     */
    public function findSimilar(
        int $documentId,
        int $limit = 5,
        float $threshold = 0.6,
        ?User $user = null
    ): Collection {
        $document = Document::findOrFail($documentId);

        // Validate user has access to the document's team
        $this->authorizeTeamAccess($document->team_id, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

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
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function getByMetadata(int $teamId, array $filters, int $limit = 100, ?User $user = null): Collection
    {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        $query = Document::where('team_id', $teamId);

        // Apply metadata filters with validation
        foreach ($filters as $key => $value) {
            $safeKey = $this->validateFilterKey($key);
            $query->whereRaw("metadata->>? = ?", [$safeKey, $value]);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Update document embedding.
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to document's team is verified.
     */
    public function updateEmbedding(Document $document, ?User $user = null): void
    {
        // Validate user has access to the document's team
        $this->authorizeTeamAccess($document->team_id, $user);

        // Pass teamId for cache isolation
        $embedding = $this->embeddingService->embed($document->content, true, $document->team_id);
        $document->setEmbedding($embedding);
    }

    /**
     * Delete documents by metadata filter.
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function deleteByMetadata(int $teamId, array $filters, ?User $user = null): int
    {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        $query = Document::where('team_id', $teamId);

        // Apply metadata filters with validation
        foreach ($filters as $key => $value) {
            $safeKey = $this->validateFilterKey($key);
            $query->whereRaw("metadata->>? = ?", [$safeKey, $value]);
        }

        return $query->delete();
    }

    /**
     * Get cluster of semantically similar documents.
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function getCluster(
        int $teamId,
        int $centroidDocumentId,
        float $threshold = 0.7,
        int $limit = 50,
        ?User $user = null
    ): Collection {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

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
     *
     * @param  User|null  $user  Optional user for authorization check. If provided, access to teamId is verified.
     */
    public function calculateTopicCoherence(int $teamId, array $documentIds, ?User $user = null): float
    {
        // Validate user has access to the team
        $this->authorizeTeamAccess($teamId, $user);

        // Limit the number of documents to prevent O(n²) resource exhaustion
        $maxDocuments = 50;
        if (count($documentIds) > $maxDocuments) {
            $documentIds = array_slice($documentIds, 0, $maxDocuments);
        }

        if (count($documentIds) < 2) {
            return 1.0;
        }

        // Verify all documents belong to the specified team
        $documents = Document::whereIn('id', $documentIds)
            ->where('team_id', $teamId)
            ->get();
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

    /**
     * Validate and sanitize metadata filter key.
     *
     * Prevents SQL injection by ensuring keys only contain safe characters.
     *
     * @throws \InvalidArgumentException if key contains invalid characters
     */
    private function validateFilterKey(string $key): string
    {
        // Only allow alphanumeric characters and underscores
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
            throw new \InvalidArgumentException("Invalid metadata filter key: {$key}. Keys must start with a letter or underscore and contain only alphanumeric characters and underscores.");
        }

        return $key;
    }

    /**
     * Apply validated metadata filters to a query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyMetadataFilters($query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            $safeKey = $this->validateFilterKey($key);

            if (is_array($value)) {
                // Escape values for PostgreSQL array literal
                $escapedValues = array_map(fn ($v) => str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v), $value);
                $query->whereRaw("metadata->>? = ANY(?)", [$safeKey, '{'.implode(',', $escapedValues).'}']);
            } else {
                $query->whereRaw("metadata->>? = ?", [$safeKey, $value]);
            }
        }
    }
}
