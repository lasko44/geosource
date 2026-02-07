# RAG (Retrieval-Augmented Generation) System

Technical documentation for the RAG implementation used in GEO scan analysis.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Core Services](#core-services)
4. [Scan Integration Flow](#scan-integration-flow)
5. [Data Flow Diagram](#data-flow-diagram)
6. [Configuration](#configuration)
7. [Database Schema](#database-schema)
8. [API Costs & Rate Limiting](#api-costs--rate-limiting)
9. [Limitations](#limitations)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The RAG system enhances GEO (Generative Engine Optimization) scans by:

1. **Semantic Search**: Finding similar previously-scanned content using vector embeddings
2. **Competitive Benchmarking**: Comparing scan scores against similar content
3. **AI Analysis**: Using LLMs to provide detailed optimization insights
4. **Improvement Suggestions**: Generating actionable recommendations based on high-performing reference content

### When RAG is Used

RAG is triggered during website scans when **all** conditions are met:

| Condition | Check | Location |
|-----------|-------|----------|
| Feature enabled | `config('rag.geo.use_rag_analysis') === true` | `config/rag.php` |
| API key present | `!empty(config('rag.openai.api_key'))` | `.env` |
| Pro or Full tier | `$tier !== GeoScorer::TIER_FREE` | Scan's `requested_tier` |

If any condition fails, the scan uses basic `GeoScorer` (rule-based scoring only).

### Tier-Based RAG Access

| Scan Tier | Pillars | RAG Features |
|-----------|---------|--------------|
| **Basic** | 5 (100 pts) | None - rule-based scoring only |
| **Pro** | 8 (135 pts) | Full RAG with embeddings, benchmarking, AI analysis |
| **Full/Agency** | 12 (175 pts) | Full RAG with embeddings, benchmarking, AI analysis |

### Document Isolation

Documents are isolated using either `team_id` or `user_id`:

| Scope | When Used | Description |
|-------|-----------|-------------|
| **Team** | `scan->team_id !== null` | Documents shared within team (preferred) |
| **User** | `scan->team_id === null` | Personal documents, isolated to user |

**Security**: All vector operations require one of these identifiers. Cross-isolation access is prevented at the query level.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SCAN REQUEST                                    │
└─────────────────────────────────────────────────────────────────────────────┘
                                     │
                                     ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           ScanWebsiteJob                                     │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────────────────────┐ │
│  │ Fetch HTML  │→ │ Extract Title│→ │ Check RAG Conditions                │ │
│  └─────────────┘  └──────────────┘  └─────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                     │
                    ┌────────────────┴────────────────┐
                    ▼                                 ▼
        ┌───────────────────┐              ┌───────────────────┐
        │    GeoScorer      │              │ EnhancedGeoScorer │
        │  (Basic Scoring)  │              │   (RAG-Enhanced)  │
        └───────────────────┘              └───────────────────┘
                                                    │
                    ┌───────────────────────────────┼───────────────────────────────┐
                    ▼                               ▼                               ▼
        ┌───────────────────┐          ┌───────────────────┐          ┌───────────────────┐
        │ ContentExtractor  │          │  EmbeddingService │          │    RAGService     │
        │ (Remove boilerplate)         │ (OpenAI/Voyage)   │          │ (LLM Analysis)    │
        └───────────────────┘          └───────────────────┘          └───────────────────┘
                                                │                               │
                                                ▼                               ▼
                                    ┌───────────────────┐          ┌───────────────────┐
                                    │   VectorStore     │          │   OpenAI/Claude   │
                                    │   (pgvector)      │          │   (Chat API)      │
                                    └───────────────────┘          └───────────────────┘
                                                │
                                                ▼
                                    ┌───────────────────┐
                                    │ ChunkingService   │
                                    │ (Semantic chunks) │
                                    └───────────────────┘
```

---

## Core Services

### 1. ContentExtractor

**Location**: `app/Services/RAG/ContentExtractor.php`

**Purpose**: Extracts main content from HTML, removing boilerplate that would dilute embedding quality.

**What it removes**:
- `<script>`, `<style>`, `<noscript>`, `<iframe>`, `<svg>`, `<form>`
- `<nav>`, `<header>`, `<footer>`, `<aside>` elements
- Elements with class/id matching: `nav`, `menu`, `sidebar`, `footer`, `header`, `breadcrumb`, `cookie`, `banner`, `advertisement`, `social`, `share`, `comment`, `popup`, `modal`, `newsletter`

**Content detection priority**:
1. `<main>` element
2. `<article>` element
3. `<div>` with class/id containing: `content`, `main`, `article`, `post`, `entry`
4. Fallback: Remove boilerplate from full HTML

**Example**:
```php
$extractor = app(ContentExtractor::class);
$cleanContent = $extractor->extract($html);

// With metadata
$result = $extractor->extractWithMetadata($html);
// Returns: ['content' => '...', 'title' => '...', 'headings' => [...], 'word_count' => 1234]
```

---

### 2. EmbeddingService

**Location**: `app/Services/RAG/EmbeddingService.php`

**Purpose**: Generates vector embeddings from text using OpenAI or Voyage AI.

**Supported Providers**:
| Provider | Model | Dimensions | Max Tokens |
|----------|-------|------------|------------|
| OpenAI | `text-embedding-3-small` (default) | 1536 | 8192 |
| OpenAI | `text-embedding-3-large` | 3072 | 8192 |
| Voyage | `voyage-2` | 1024 | 4000 |

**Key Features**:
- **Caching**: 7-day cache with team isolation (cache key includes `team_id`)
- **Rate Limiting**: 60 requests/min per team, 10 batch requests/min
- **Safe Truncation**: Text truncated to ~22,500 chars (7500 tokens × 3 chars/token)
- **Batch Processing**: Up to 100 texts per batch request

**Cache Isolation**:
```php
// Cache key format prevents cross-team data inference
$cacheKey = 'embedding:' . hash('sha256', $text . $model . ":team:{$teamId}");
```

---

### 3. ChunkingService

**Location**: `app/Services/RAG/ChunkingService.php`

**Purpose**: Splits content into chunks for embedding. Smaller chunks = more precise retrieval.

**Strategies**:

| Strategy | Description | Best For |
|----------|-------------|----------|
| `semantic` (default) | Split by HTML headings (h1-h6) | Structured articles |
| `fixed` | Fixed character size with overlap | Unstructured text |
| `sentence` | Group sentences to target size | Prose content |
| `paragraph` | Split by paragraphs | Blog posts |

**Contextual Chunking** (enabled by default):

Each chunk is prefixed with document context for better embeddings:
```
From: How to Optimize Your Website for AI
Section: Technical SEO Basics

The actual chunk content starts here...
```

**Configuration**:
```php
'chunking' => [
    'strategy' => 'semantic',
    'size' => 1000,        // Target chunk size (chars)
    'overlap' => 200,      // Overlap between chunks
    'contextual' => true,  // Prepend title/section
]
```

---

### 4. VectorStore

**Location**: `app/Services/RAG/VectorStore.php`

**Purpose**: Manages document storage and retrieval using PostgreSQL with pgvector.

**Key Operations**:

| Method | Description |
|--------|-------------|
| `addDocument()` | Store content with chunking and embedding |
| `search()` | Semantic similarity search |
| `searchByVector()` | Search using pre-computed embedding |
| `hybridSearch()` | Combined semantic + keyword search |
| `findSimilar()` | Find documents similar to existing document |

**Hybrid Search Formula**:
```sql
combined_score = (0.7 × semantic_similarity) + (0.3 × keyword_rank)
```

**Metadata Filters**:
```php
// Exact match (with team isolation)
$vectorStore->search($query, $teamId, $userId, filters: ['type' => 'scanned_page']);

// Comparison operators (via suffixes)
$vectorStore->search($query, $teamId, $userId, filters: [
    'geo_score_min' => 70,  // geo_score >= 70
    'geo_score_max' => 90,  // geo_score <= 90
]);
```

**Isolation**:
- All methods accept both `$teamId` and `$userId` parameters
- If `$teamId` is provided, documents are isolated by team
- If only `$userId` is provided, documents are isolated by user
- Validation ensures at least one isolation parameter is provided

**Security**:
- All queries include either `team_id` or `user_id` constraint
- Optional `$user` parameter for request-level authorization
- Maximum 100 results per query (prevents resource exhaustion)

---

### 5. RAGService

**Location**: `app/Services/RAG/RAGService.php`

**Purpose**: Orchestrates retrieval and LLM generation for analysis.

**Key Methods**:

| Method | Purpose | Used In Scans |
|--------|---------|---------------|
| `retrieve()` | Vector search with re-ranking | Yes |
| `retrieveContext()` | Hybrid search + format for prompts | Yes |
| `analyzeForGEO()` | AI analysis of content quality | Yes |
| `suggestImprovements()` | Generate improvement suggestions | Yes |
| `answerQuestion()` | Q&A based on indexed content | No |
| `summarizeTopic()` | Multi-document summarization | No |
| `findContentGaps()` | Identify missing subtopics | No |

**Re-ranking Process**:
```
1. Retrieve top 20 results via vector search (low threshold: 0.35)
2. Send to LLM for relevance scoring (0.0 - 1.0)
3. Combine: final_score = (0.6 × LLM_score) + (0.4 × vector_similarity)
4. Return top 5 results
```

**Context Limits**:
```php
'context' => [
    'max_context_chars' => 20000,  // Per-prompt context limit
    'max_content_chars' => 25000,  // Content for analysis
]
```

**Prompt Injection Protection**:
- Escapes `<` and `>` to prevent delimiter manipulation
- Filters common injection patterns: "ignore previous", "new instructions:", etc.
- Uses XML-like tags to separate user data from instructions

---

### 6. EnhancedGeoScorer

**Location**: `app/Services/GEO/EnhancedGeoScorer.php`

**Purpose**: Combines traditional GEO scoring with RAG enhancements.

**Analysis Flow**:
```php
public function analyze(string $content, int $teamId, array $options = []): array
{
    // 1. Extract main content (removes boilerplate)
    $extracted = $this->contentExtractor->extractWithMetadata($content);

    // 2. Generate embedding from clean content
    $embedding = $this->embeddingService->embed($extracted['content'], true, $teamId);

    // 3. Get base GEO score (uses original HTML for structure analysis)
    $baseScore = $this->baseScorer->score($content, $context);

    // 4. Find similar content for benchmarking
    $similarContent = $this->vectorStore->searchByVector($embedding, $teamId);

    // 5. Calculate benchmark position
    $benchmark = $this->calculateBenchmark($baseScore, $similarContent);

    // 6. Get AI analysis and suggestions
    $ragAnalysis = $this->ragService->analyzeForGEO($content, $teamId);
    $aiSuggestions = $this->ragService->suggestImprovements($content, $baseScore, $teamId);

    return [...];
}
```

---

## Scan Integration Flow

### Step-by-Step Process

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 1. USER CREATES SCAN                                                         │
│    POST /scans                                                               │
│    └─→ ScanController::store()                                               │
│        └─→ ScanService::executeScan()                                        │
│            ├─→ Deduct tokens                                                 │
│            ├─→ Create Scan record (status: 'pending')                        │
│            └─→ Dispatch ScanWebsiteJob                                       │
└──────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ 2. JOB STARTS (ScanWebsiteJob::handle)                                       │
│    ├─→ Verify subscription still valid                                       │
│    ├─→ Fetch webpage (HTTP → Browsershot fallback)                          │
│    └─→ Extract page title                                                    │
└──────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ 3. RAG DECISION POINT                                                        │
│    $ragAvailable = config('rag.geo.use_rag_analysis')                       │
│                    && !empty(config('rag.openai.api_key'))                  │
│                                                                              │
│    $useEnhanced = $ragAvailable                                             │
│                   && $tier !== TIER_FREE  // Pro or Full tier only          │
│                                                                              │
│    Document isolation uses team_id if available, otherwise user_id          │
│                                                                              │
│    if ($useEnhanced) → EnhancedGeoScorer (RAG + rule-based)                 │
│    else              → GeoScorer (rule-based only)                          │
└──────────────────────────────────────────────────────────────────────────────┘
                                      │
                    ┌─────────────────┴─────────────────┐
                    ▼                                   ▼
┌────────────────────────────┐        ┌────────────────────────────────────────┐
│ BASIC PATH (GeoScorer)     │        │ ENHANCED PATH (EnhancedGeoScorer)      │
│ ├─→ Check page structure   │        │                                        │
│ ├─→ Analyze definitions    │        │ 4a. CONTENT EXTRACTION                 │
│ ├─→ Check machine-readable │        │     ContentExtractor::extract($html)   │
│ ├─→ Evaluate authority     │        │     └─→ Remove nav, footer, sidebar   │
│ └─→ Return score + grade   │        │     └─→ Find <main> or <article>      │
└────────────────────────────┘        │     └─→ Clean text output              │
                                      │                                        │
                                      │ 4b. EMBEDDING GENERATION                │
                                      │     EmbeddingService::embed()          │
                                      │     └─→ Check cache (team-isolated)    │
                                      │     └─→ Call OpenAI API if miss        │
                                      │     └─→ Return 1536-dim vector         │
                                      │                                        │
                                      │ 4c. BASE SCORING                        │
                                      │     GeoScorer::score($html)            │
                                      │     └─→ Uses original HTML             │
                                      │     └─→ Structure, definitions, etc.   │
                                      │                                        │
                                      │ 4d. SIMILARITY SEARCH                   │
                                      │     VectorStore::searchByVector()      │
                                      │     └─→ Find 5 similar documents       │
                                      │     └─→ Threshold: 0.35                │
                                      │     └─→ Team-isolated results          │
                                      │                                        │
                                      │ 4e. BENCHMARK CALCULATION               │
                                      │     calculateBenchmark()               │
                                      │     └─→ Score similar documents        │
                                      │     └─→ Calculate percentile           │
                                      │     └─→ Determine position             │
                                      │                                        │
                                      │ 4f. AI ANALYSIS (if API key present)   │
                                      │     RAGService::analyzeForGEO()        │
                                      │     └─→ Build analysis prompt          │
                                      │     └─→ Call LLM (gpt-4o-mini)        │
                                      │     └─→ Parse JSON response            │
                                      │                                        │
                                      │ 4g. IMPROVEMENT SUGGESTIONS             │
                                      │     RAGService::suggestImprovements()  │
                                      │     └─→ Find high-scoring references   │
                                      │     └─→ Generate actionable items      │
                                      └────────────────────────────────────────┘
                                                        │
                                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ 5. SAVE RESULTS                                                              │
│    $scan->update([                                                           │
│        'score' => $result['score'],                                          │
│        'grade' => $result['grade'],                                          │
│        'results' => $result,  // Contains all RAG data                       │
│        'status' => 'completed',                                              │
│    ]);                                                                       │
└──────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ 6. STORE IN VECTOR DB (if RAG enabled)                                       │
│    ContentExtractor::extract($html)  // Clean content                        │
│    VectorStore::addDocument(                                                 │
│        $teamId,      // Team isolation (if team scan)                        │
│        $userId,      // User isolation (if personal scan)                    │
│        $title,                                                               │
│        $extractedContent,                                                    │
│        metadata: [                                                           │
│            'type' => 'scanned_page',                                         │
│            'url' => $url,                                                    │
│            'scan_id' => $scanId,                                             │
│            'geo_score' => $score,                                            │
│        ],                                                                    │
│        chunk: true                                                           │
│    );                                                                        │
│                                                                              │
│    Chunking process:                                                         │
│    └─→ ChunkingService::chunk() (semantic strategy)                         │
│    └─→ ChunkingService::createSummaryChunk()                                │
│    └─→ Apply contextual prefixes ("From: Title\nSection: Heading")          │
│    └─→ EmbeddingService::embedBatch() for all chunks                        │
│    └─→ Store each chunk as Document with embedding                          │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagram

### Embedding Flow
```
Raw HTML (50KB+)
    │
    ▼
ContentExtractor
    │ Removes: nav, footer, scripts, ads
    ▼
Clean Text (5-15KB)
    │
    ▼
EmbeddingService
    │ Truncates to ~22,500 chars
    │ Checks cache (team-isolated)
    │ Calls OpenAI if cache miss
    ▼
Vector [1536 floats]
    │
    ▼
PostgreSQL (pgvector)
```

### Search Flow
```
Query Text
    │
    ▼
EmbeddingService::embed()
    │
    ▼
Query Vector [1536]
    │
    ├──────────────────────────────────────┐
    ▼                                      ▼
VectorStore::searchByVector()       VectorStore::hybridSearch()
    │                                      │
    │ SELECT ... ORDER BY                  │ SELECT ...
    │ embedding <=> query_vector           │ (0.7 × cosine_sim) +
    │ WHERE team_id = ?                    │ (0.3 × ts_rank)
    │ AND similarity >= 0.35               │
    ▼                                      ▼
Top 20 Results                       Top 20 Results
    │                                      │
    └──────────────┬───────────────────────┘
                   ▼
            RAGService::rerank()
                   │
                   │ LLM scores each result
                   │ Combines: 0.6×LLM + 0.4×vector
                   ▼
            Top 5 Results (re-ranked)
```

---

## Configuration

### Environment Variables

```bash
# Feature Toggle
GEO_USE_RAG=true

# OpenAI (required for RAG)
OPENAI_API_KEY=sk-...

# Embedding Settings
EMBEDDING_PROVIDER=openai          # openai | voyage
EMBEDDING_MODEL=text-embedding-3-small
EMBEDDING_DIMENSIONS=1536

# LLM Settings
LLM_PROVIDER=openai                # openai | anthropic
LLM_MODEL=gpt-4o-mini

# Chunking
CHUNKING_STRATEGY=semantic         # semantic | fixed | sentence | paragraph
CHUNK_SIZE=1000
CHUNK_OVERLAP=200
CHUNKING_CONTEXTUAL=true

# Search
RAG_SEARCH_THRESHOLD=0.35
RAG_RERANK_ENABLED=true
RAG_RERANK_TOP_N=20
RAG_RERANK_FINAL_N=5

# Context Limits
RAG_MAX_CONTEXT_CHARS=20000
RAG_MAX_CONTENT_CHARS=25000

# Content Extraction
RAG_CONTENT_EXTRACTION=true
```

### Full Config Reference

See `config/rag.php` for all options with documentation.

---

## Database Schema

### Documents Table

```sql
CREATE TABLE documents (
    id BIGINT PRIMARY KEY,
    team_id BIGINT REFERENCES teams(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    metadata JSONB DEFAULT '{}',
    embedding vector(1536),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Vector similarity index (IVFFlat for approximate nearest neighbor)
CREATE INDEX documents_embedding_idx
ON documents USING ivfflat (embedding vector_cosine_ops)
WITH (lists = 100);

-- Team filtering index
CREATE INDEX documents_team_id_idx ON documents(team_id);

-- User filtering index (for personal document isolation)
CREATE INDEX documents_user_id_idx ON documents(user_id);

-- Full-text search index for hybrid search
CREATE INDEX documents_content_fts_idx
ON documents USING gin(to_tsvector('english', content));
```

**Isolation Rules**:
- Documents have either `team_id` (team scans) OR `user_id` (personal scans) set
- Never both - this prevents confusion about ownership
- Queries always filter by one isolation scope

### Metadata Structure

```json
{
    "type": "scanned_page",
    "url": "https://example.com/page",
    "scan_id": 12345,
    "geo_score": 78,
    "chunk_type": "section",
    "section_heading": "Introduction",
    "parent_title": "Full Page Title",
    "total_chunks": 8,
    "has_context_prefix": true,
    "is_summary": false
}
```

---

## API Costs & Rate Limiting

### OpenAI API Costs (as of 2024)

| Service | Model | Cost |
|---------|-------|------|
| Embeddings | text-embedding-3-small | $0.02 / 1M tokens |
| Embeddings | text-embedding-3-large | $0.13 / 1M tokens |
| Chat | gpt-4o-mini | $0.15 / 1M input, $0.60 / 1M output |
| Chat | gpt-4o | $2.50 / 1M input, $10.00 / 1M output |

### Per-Scan Cost Estimate

| Operation | Tokens | Cost (gpt-4o-mini) |
|-----------|--------|-------------------|
| Page embedding | ~5,000 | $0.0001 |
| Chunk embeddings (8 chunks) | ~8,000 | $0.00016 |
| GEO analysis prompt | ~3,000 | $0.00045 |
| GEO analysis response | ~1,500 | $0.0009 |
| Suggestions prompt | ~2,500 | $0.000375 |
| Suggestions response | ~1,000 | $0.0006 |
| Re-ranking (if enabled) | ~2,000 | $0.0003 |
| **Total per scan** | **~23,000** | **~$0.003** |

### Rate Limits

| Limit | Value | Scope |
|-------|-------|-------|
| Embedding requests | 60/min | Per team |
| Batch embedding requests | 10/min | Per team |
| Search results | 100 max | Per query |
| Batch size | 100 texts | Per request |

---

## Limitations

### 1. Content Extraction Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| JavaScript-rendered content not extracted | SPAs may have empty content | Browsershot fallback renders JS |
| Complex layouts may confuse extractor | Main content misidentified | Falls back to boilerplate removal |
| Non-English content | Heading patterns may not match | Works but less precise |
| PDF/image content | Cannot extract text from images | Only HTML content supported |

### 2. Embedding Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Token limit (8192) | Long pages truncated | Truncation at ~22,500 chars |
| Semantic drift | Similar words ≠ similar meaning | Hybrid search adds keyword matching |
| Cold cache on new teams | First scans slower | Cache warms over time |
| Cross-language similarity | Different languages don't match well | Designed for English content |

### 3. Vector Search Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Team isolation | Can only compare within team | Intentional for security |
| New teams have no data | No benchmarking possible | Shows "No similar content found" |
| IVFFlat approximation | ~95% recall, not 100% | Acceptable trade-off for speed |
| Threshold too strict | May miss relevant content | Lowered to 0.35, re-ranking filters |

### 4. Re-ranking Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Adds LLM API call | ~200ms latency, ~$0.0003 cost | Can be disabled via config |
| LLM inconsistency | Scores may vary on repeat | Combined with vector similarity |
| Context truncation | Only 500 chars per doc for scoring | Enough for relevance judgment |

### 5. LLM Analysis Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Hallucination risk | May suggest non-existent issues | Cross-reference with rule-based scores |
| JSON parsing failures | Analysis may fail | Fallback to raw response |
| Rate limits | May fail under high load | Retry logic in job queue |
| Cost accumulation | High-volume usage expensive | Token-based billing to users |

### 6. Chunking Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Context loss between chunks | Chunk may lack context | Contextual prefixes added |
| Semantic strategy needs headings | Falls back to fixed if no headings | Graceful degradation |
| Overlap increases storage | More chunks = more embeddings | Overlap limited to 200 chars |

### 7. Benchmarking Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Requires similar content | New topics have no benchmark | Shows "unknown" position |
| Score comparison only | Doesn't explain why scores differ | AI analysis provides context |
| Historical bias | Old content may have outdated patterns | Recency could be weighted |

### 8. Tier & Isolation Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Basic tier excluded | Basic scans never use RAG | Upgrade to Pro/Full for RAG features |
| No cross-isolation comparison | Can only benchmark within same team or user's documents | Intentional for data isolation |
| Personal scans have limited benchmarking | Only user's own past scans for comparison | Join a team for broader benchmarking |

### 9. Security Considerations

| Risk | Mitigation |
|------|------------|
| Cross-isolation data leakage | All queries filtered by `team_id` or `user_id` |
| Prompt injection | Input sanitization, XML delimiters |
| Cache timing attacks | Isolation-scoped cache keys |
| Resource exhaustion | Rate limiting, max result limits |
| Missing isolation | Validation throws error if neither team_id nor user_id provided |

---

## Troubleshooting

### RAG Not Running

```php
// Check conditions
$ragAvailable = config('rag.geo.use_rag_analysis')   // Must be true
    && !empty(config('rag.openai.api_key'));          // Must have API key

$useEnhanced = $ragAvailable
    && $tier !== GeoScorer::TIER_FREE;                // Must be Pro or Full tier

// Documents are isolated by team_id (if available) or user_id (fallback)
```

**Solutions**:
1. Set `GEO_USE_RAG=true` in `.env`
2. Add `OPENAI_API_KEY=sk-...` in `.env`
3. Use Pro or Full tier scan (Basic tier never uses RAG)

### No Similar Content Found

**Cause**: Team has no previously scanned content, or content is too different.

**Solutions**:
1. Scan more pages to build the knowledge base
2. Lower threshold: `RAG_SEARCH_THRESHOLD=0.25`

### Slow Scan Performance

**Causes**:
1. Embedding API latency
2. Re-ranking LLM calls
3. Large HTML pages

**Solutions**:
1. Check embedding cache hit rate
2. Disable re-ranking: `RAG_RERANK_ENABLED=false`
3. Verify content extraction is working

### High API Costs

**Solutions**:
1. Use `text-embedding-3-small` instead of `large`
2. Use `gpt-4o-mini` instead of `gpt-4o`
3. Reduce `RAG_RERANK_TOP_N` from 20 to 10
4. Disable re-ranking if not needed

### Vector Store Errors

```sql
-- Check pgvector extension
SELECT * FROM pg_extension WHERE extname = 'vector';

-- Verify index exists
SELECT indexname FROM pg_indexes WHERE tablename = 'documents';

-- Check document count per team
SELECT team_id, COUNT(*) FROM documents GROUP BY team_id;
```

---

## Appendix: Result Structure

When RAG is enabled, scan results include:

```json
{
    "score": 78,
    "max_score": 100,
    "percentage": 78,
    "grade": "B+",
    "pillars": {
        "definitions": { "score": 8, "max": 10, "items": [...] },
        "structure": { "score": 7, "max": 10, "items": [...] },
        "authority": { "score": 6, "max": 10, "items": [...] },
        "machine_readable": { "score": 9, "max": 10, "items": [...] },
        "answerability": { "score": 8, "max": 10, "items": [...] }
    },
    "recommendations": ["Add schema markup", "Include more citations"],
    "summary": "Good overall structure with room for improvement in authority signals.",

    "benchmark": {
        "position": "above_average",
        "percentile": 72,
        "avg_similar_score": 71.5,
        "score_difference": 6.5,
        "comparison": "Good performance. You're 6.5 points above the average."
    },

    "similar_content": [
        { "id": 123, "title": "Similar Page 1", "similarity": 0.82 },
        { "id": 456, "title": "Similar Page 2", "similarity": 0.76 }
    ],

    "rag_analysis": {
        "clarity_score": 8,
        "structure_score": 7,
        "answerability_score": 8,
        "strengths": ["Clear definitions", "Good heading structure"],
        "weaknesses": ["Missing citations", "No FAQ section"],
        "specific_improvements": [
            {
                "area": "Authority",
                "current": "No external citations",
                "suggested": "Add 2-3 authoritative source citations",
                "reason": "Improves trustworthiness for AI systems"
            }
        ],
        "quotable_snippets": ["GEO is the practice of optimizing content for AI systems."],
        "missing_elements": ["FAQ section", "Author bio"],
        "overall_assessment": "Well-structured content with strong definitions..."
    },

    "ai_suggestions": [
        {
            "priority": "high",
            "category": "authority",
            "original": "No citations present",
            "improved": "Add: 'According to [Source], ...'",
            "explanation": "AI systems prefer content with verifiable sources"
        }
    ],

    "embedding_generated": true,
    "scored_at": "2024-01-15T10:30:00Z"
}
```
