<?php

namespace App\Services\RAG;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Vector store for managing document embeddings with pgvector.
 *
 * Provides methods for:
 * - Storing documents with embeddings
 * - Similarity search
 * - Hybrid search (semantic + keyword)
 * - Metadata filtering
 *
 * Security: All operations require either team_id or user_id for isolation.
 * When a User is provided, access is verified before any operation.
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
    private function authorizeTeamAccess(?int $teamId, ?User $user): void
    {
        if ($user === null || $teamId === null) {
            return; // No user or no team provided, skip authorization
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
     * Validate isolation parameters - must have either team_id or user_id.
     *
     * @throws \InvalidArgumentException
     */
    private function validateIsolation(?int $teamId, ?int $userId): void
    {
        if ($teamId === null && $userId === null) {
            throw new \InvalidArgumentException(
                'Either team_id or user_id must be provided for document isolation.'
            );
        }
    }

    /**
     * Get the isolation ID for caching (prefers team_id, falls back to user_id).
     */
    private function getIsolationId(?int $teamId, ?int $userId): int
    {
        return $teamId ?? $userId;
    }

    /**
     * Get isolation type for logging/debugging.
     */
    private function getIsolationType(?int $teamId, ?int $userId): string
    {
        return $teamId !== null ? 'team' : 'user';
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
     * Documents are isolated by either team_id or user_id.
     *
     * @param  int|null  $teamId  Team ID for team isolation (preferred)
     * @param  int|null  $userId  User ID for personal isolation (fallback)
     * @param  User|null  $user  Optional user for authorization check
     */
    public function addDocument(
        ?int $teamId,
        ?int $userId,
        string $title,
        string $content,
        array $metadata = [],
        bool $chunk = true,
        ?User $user = null
    ): array {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        $isolationId = $this->getIsolationId($teamId, $userId);

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

            // Generate embeddings in batch (with isolation for cache)
            $texts = array_column($chunks, 'content');
            $embeddings = $this->embeddingService->embedBatch($texts, true, $isolationId);

            // Store each chunk as a document
            foreach ($chunks as $index => $chunk) {
                $doc = Document::create([
                    'team_id' => $teamId,
                    'user_id' => $userId,
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
            // Store as single document (with isolation for cache)
            $embedding = $this->embeddingService->embed($content, true, $isolationId);

            $doc = Document::create([
                'team_id' => $teamId,
                'user_id' => $userId,
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
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function search(
        string $query,
        ?int $teamId,
        ?int $userId = null,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = [],
        ?User $user = null
    ): Collection {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        // Use isolation ID for cache
        $isolationId = $this->getIsolationId($teamId, $userId);
        $queryEmbedding = $this->embeddingService->embed($query, true, $isolationId);

        return $this->searchByVector($queryEmbedding, $teamId, $userId, $limit, $threshold, $filters);
    }

    /**
     * Search by vector directly.
     *
     * Note: Uses raw expressions for pgvector cosine similarity operator (<=>)
     * which is not supported by Eloquent natively.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function searchByVector(
        array $vector,
        ?int $teamId,
        ?int $userId = null,
        int $limit = 10,
        float $threshold = 0.5,
        array $filters = [],
        ?User $user = null
    ): Collection {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        $vectorString = '['.implode(',', $vector).']';

        // Raw expressions required for pgvector cosine distance operator (<=>)
        $query = Document::query()
            ->select([
                'id',
                'title',
                'content',
                'metadata',
                'created_at',
            ])
            ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->whereNotNull('embedding')
            ->whereRaw('1 - (embedding <=> ?::vector) >= ?', [$vectorString, $threshold]);

        // Apply isolation filter (team or user)
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId);
        }

        // Apply metadata filters with validation
        $this->applyMetadataFilters($query, $filters);

        return $query
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit($limit)
            ->get();
    }

    /**
     * Hybrid search combining semantic and keyword search.
     *
     * Note: Uses raw expressions for pgvector cosine similarity operator (<=>)
     * and PostgreSQL full-text search functions which are not supported by Eloquent natively.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function hybridSearch(
        string $query,
        ?int $teamId,
        ?int $userId = null,
        int $limit = 10,
        float $semanticWeight = 0.7,
        array $filters = [],
        ?User $user = null
    ): Collection {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        // Use isolation ID for cache
        $isolationId = $this->getIsolationId($teamId, $userId);
        $queryEmbedding = $this->embeddingService->embed($query, true, $isolationId);
        $vectorString = '['.implode(',', $queryEmbedding).']';

        // Normalize the query for text search
        $searchTerms = $this->prepareSearchTerms($query);

        // Raw expressions required for pgvector (<=>), ts_rank, and to_tsvector
        $results = Document::query()
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
            ->whereNotNull('embedding');

        // Apply isolation filter (team or user)
        if ($teamId !== null) {
            $results->where('team_id', $teamId);
        } else {
            $results->where('user_id', $userId);
        }

        // Apply metadata filters with validation
        $this->applyMetadataFilters($results, $filters);

        return $results
            ->orderByDesc('combined_score')
            ->limit($limit)
            ->get()
            ->each(function ($doc) {
                $doc->similarity = $doc->combined_score;
            });
    }

    /**
     * Find documents similar to an existing document.
     *
     * Note: Uses raw expressions for pgvector cosine similarity operator (<=>)
     * which is not supported by Eloquent natively.
     *
     * @param  User|null  $user  Optional user for authorization check
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

        // Raw expressions required for pgvector cosine distance operator (<=>)
        $query = Document::query()
            ->select(['id', 'title', 'content', 'metadata'])
            ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->where('id', '!=', $documentId)
            ->whereNotNull('embedding')
            ->whereRaw('1 - (embedding <=> ?::vector) >= ?', [$vectorString, $threshold]);

        // Apply same isolation as the source document
        if ($document->team_id !== null) {
            $query->where('team_id', $document->team_id);
        } else {
            $query->where('user_id', $document->user_id);
        }

        return $query
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit($limit)
            ->get();
    }

    /**
     * Get documents by metadata filter.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function getByMetadata(
        ?int $teamId,
        ?int $userId,
        array $filters,
        int $limit = 100,
        ?User $user = null
    ): Collection {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        $query = Document::query();

        // Apply isolation filter (team or user)
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId);
        }

        // Apply metadata filters with validation
        foreach ($filters as $key => $value) {
            $safeKey = $this->validateFilterKey($key);
            $query->whereRaw('metadata->>? = ?', [$safeKey, $value]);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Update document embedding.
     *
     * @param  User|null  $user  Optional user for authorization check
     */
    public function updateEmbedding(Document $document, ?User $user = null): void
    {
        // Validate user has access to the document's team
        $this->authorizeTeamAccess($document->team_id, $user);

        // Use isolation ID for cache (team or user)
        $isolationId = $this->getIsolationId($document->team_id, $document->user_id);
        $embedding = $this->embeddingService->embed($document->content, true, $isolationId);
        $document->setEmbedding($embedding);
    }

    /**
     * Delete documents by metadata filter.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function deleteByMetadata(
        ?int $teamId,
        ?int $userId,
        array $filters,
        ?User $user = null
    ): int {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        $query = Document::query();

        // Apply isolation filter (team or user)
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId);
        }

        // Apply metadata filters with validation
        foreach ($filters as $key => $value) {
            $safeKey = $this->validateFilterKey($key);
            $query->whereRaw('metadata->>? = ?', [$safeKey, $value]);
        }

        return $query->delete();
    }

    /**
     * Get cluster of semantically similar documents.
     *
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function getCluster(
        ?int $teamId,
        ?int $userId,
        int $centroidDocumentId,
        float $threshold = 0.7,
        int $limit = 50,
        ?User $user = null
    ): Collection {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Enforce max limit to prevent resource exhaustion
        $limit = $this->enforceSearchLimit($limit);

        $centroid = Document::findOrFail($centroidDocumentId);
        $embedding = $centroid->getEmbedding();

        if (! $embedding) {
            return collect([$centroid]);
        }

        return $this->searchByVector($embedding, $teamId, $userId, $limit, $threshold)
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
     * @param  int|null  $teamId  Team ID for team isolation
     * @param  int|null  $userId  User ID for personal isolation
     * @param  User|null  $user  Optional user for authorization check
     */
    public function calculateTopicCoherence(
        ?int $teamId,
        ?int $userId,
        array $documentIds,
        ?User $user = null
    ): float {
        $this->validateIsolation($teamId, $userId);
        $this->authorizeTeamAccess($teamId, $user);

        // Limit the number of documents to prevent O(n²) resource exhaustion
        $maxDocuments = 50;
        if (count($documentIds) > $maxDocuments) {
            $documentIds = array_slice($documentIds, 0, $maxDocuments);
        }

        if (count($documentIds) < 2) {
            return 1.0;
        }

        // Verify all documents belong to the specified isolation scope
        $query = Document::whereIn('id', $documentIds);
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        } else {
            $query->where('user_id', $userId);
        }

        $documents = $query->get();
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
     * Supports comparison operators via key suffixes:
     * - '_min' suffix: >= comparison (e.g., 'geo_score_min' => 70)
     * - '_max' suffix: <= comparison (e.g., 'geo_score_max' => 90)
     * - '_gt' suffix: > comparison
     * - '_lt' suffix: < comparison
     * - No suffix: exact match
     *
     * Also supports boolean values which are cast appropriately for JSONB comparison.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyMetadataFilters($query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            // Check for comparison operator suffixes
            $operator = '=';
            $actualKey = $key;

            if (str_ends_with($key, '_min')) {
                $operator = '>=';
                $actualKey = substr($key, 0, -4);
            } elseif (str_ends_with($key, '_max')) {
                $operator = '<=';
                $actualKey = substr($key, 0, -4);
            } elseif (str_ends_with($key, '_gt')) {
                $operator = '>';
                $actualKey = substr($key, 0, -3);
            } elseif (str_ends_with($key, '_lt')) {
                $operator = '<';
                $actualKey = substr($key, 0, -3);
            }

            $safeKey = $this->validateFilterKey($actualKey);

            if (is_array($value)) {
                // Escape values for PostgreSQL array literal
                $escapedValues = array_map(fn ($v) => str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v), $value);
                $query->whereRaw('metadata->>? = ANY(?)', [$safeKey, '{'.implode(',', $escapedValues).'}']);
            } elseif (is_bool($value)) {
                // Boolean comparison - cast JSON value to boolean
                // JSONB stores booleans as true/false, ->> returns string 'true'/'false'
                $query->whereRaw("(metadata->>?)::boolean = ?", [$safeKey, $value]);
            } elseif ($operator !== '=') {
                // Numeric comparison - cast JSON value to numeric
                $query->whereRaw("(metadata->>?)::numeric {$operator} ?", [$safeKey, $value]);
            } else {
                $query->whereRaw('metadata->>? = ?', [$safeKey, $value]);
            }
        }
    }
}
