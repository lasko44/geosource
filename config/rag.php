<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for OpenAI services (embeddings and chat completions).
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for Anthropic Claude models.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voyage AI Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for Voyage AI embeddings (alternative to OpenAI).
    |
    */

    'voyage' => [
        'api_key' => env('VOYAGE_API_KEY'),
        'model' => env('VOYAGE_EMBEDDING_MODEL', 'voyage-2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for vector embeddings generation.
    |
    | Providers: 'openai', 'voyage'
    | Models (OpenAI): 'text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'
    | Dimensions: 1536 (default for most models), can be reduced for text-embedding-3-*
    |
    */

    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'openai'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 1536),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the language model used in RAG generation.
    |
    | Providers: 'openai', 'anthropic'
    | Models (OpenAI): 'gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo'
    | Models (Anthropic): 'claude-3-haiku-20240307', 'claude-3-sonnet-20240229'
    |
    */

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai'),
        'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunking Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for how content is split into chunks for embedding.
    |
    | Strategies: 'semantic', 'fixed', 'sentence', 'paragraph'
    | Size: Target chunk size in characters
    | Overlap: Character overlap between chunks for context preservation
    | Contextual: Prepend document title/section to chunks for better embeddings
    |
    */

    'chunking' => [
        'strategy' => env('CHUNKING_STRATEGY', 'semantic'),
        'size' => (int) env('CHUNK_SIZE', 1000),
        'overlap' => (int) env('CHUNK_OVERLAP', 200),
        'contextual' => env('CHUNKING_CONTEXTUAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for vector similarity search.
    |
    | default_threshold: Lowered to 0.35 for better recall (re-ranking filters)
    | rerank_enabled: Whether to re-rank results using cross-encoder scoring
    | rerank_top_n: Number of results to retrieve before re-ranking
    | rerank_final_n: Number of results to return after re-ranking
    |
    */

    'search' => [
        'default_limit' => 10,
        'default_threshold' => (float) env('RAG_SEARCH_THRESHOLD', 0.35),
        'hybrid_semantic_weight' => 0.7,
        'rerank_enabled' => env('RAG_RERANK_ENABLED', true),
        'rerank_top_n' => (int) env('RAG_RERANK_TOP_N', 20),
        'rerank_final_n' => (int) env('RAG_RERANK_FINAL_N', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for context handling in RAG generation.
    |
    | max_context_chars: Maximum characters for context in LLM prompts
    | max_content_chars: Maximum characters for content analysis
    |
    */

    'context' => [
        'max_context_chars' => (int) env('RAG_MAX_CONTEXT_CHARS', 20000),
        'max_content_chars' => (int) env('RAG_MAX_CONTENT_CHARS', 25000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Extraction
    |--------------------------------------------------------------------------
    |
    | Settings for extracting main content from HTML before embedding.
    |
    */

    'extraction' => [
        'enabled' => env('RAG_CONTENT_EXTRACTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | GEO Enhancement
    |--------------------------------------------------------------------------
    |
    | Settings for GEO scoring enhancements using RAG.
    |
    */

    'geo' => [
        'use_rag_analysis' => env('GEO_USE_RAG', true),
        'comparison_limit' => 5,
        'min_similarity_for_comparison' => 0.35,
    ],

];
