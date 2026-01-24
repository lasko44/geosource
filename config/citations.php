<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Perplexity API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Perplexity AI API integration.
    |
    */

    'perplexity' => [
        'api_key' => env('PERPLEXITY_API_KEY'),
        'base_url' => 'https://api.perplexity.ai',
        'model' => 'sonar',
        'timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI API with web browsing capability.
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => 'gpt-4o',
        'timeout' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Claude API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Anthropic Claude API.
    |
    */

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => 'claude-haiku-4-5-20251001', // Fast and cheap for citation checks
        'timeout' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Brave Search API Configuration
    |--------------------------------------------------------------------------
    |
    | Used to give Claude web search capabilities.
    | Get a free API key at: https://brave.com/search/api/
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Tavily Search API Configuration
    |--------------------------------------------------------------------------
    |
    | Used to give Claude web search capabilities.
    | Get a free API key at: https://tavily.com
    |
    */

    'tavily' => [
        'api_key' => env('TAVILY_API_KEY'),
        'base_url' => 'https://api.tavily.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Gemini API.
    |
    */

    'gemini' => [
        'api_key' => env('GOOGLE_AI_API_KEY'),
        'model' => 'gemini-2.0-flash', // Supports Google Search Grounding
        'timeout' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | GA4 Configuration
    |--------------------------------------------------------------------------
    |
    | Google Analytics 4 OAuth and API configuration.
    |
    */

    'ga4' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('APP_URL').'/analytics/ga4/callback',
        'scopes' => [
            'https://www.googleapis.com/auth/analytics.readonly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Referral Sources
    |--------------------------------------------------------------------------
    |
    | List of known AI platform referral sources for GA4 tracking.
    |
    */

    'ai_referral_sources' => [
        'perplexity.ai',
        'chat.openai.com',
        'chatgpt.com',
        'claude.ai',
        'gemini.google.com',
        'copilot.microsoft.com',
        'you.com',
        'phind.com',
        'bard.google.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Display Configuration
    |--------------------------------------------------------------------------
    |
    | Display names and styling for each platform.
    |
    */

    'platforms' => [
        'perplexity' => [
            'name' => 'Perplexity AI',
            'color' => '#20B2AA',
            'icon' => 'perplexity',
        ],
        'openai' => [
            'name' => 'ChatGPT',
            'color' => '#10A37F',
            'icon' => 'openai',
        ],
        'claude' => [
            'name' => 'Claude',
            'color' => '#D97706',
            'icon' => 'claude',
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'color' => '#4285F4',
            'icon' => 'gemini',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Citation Check Settings
    |--------------------------------------------------------------------------
    |
    | Settings for citation checking behavior.
    |
    */

    'check' => [
        // Maximum retries for failed checks
        'max_retries' => 3,

        // Delay between retries in seconds
        'retry_delay' => 30,

        // Cleanup: delete checks older than this many days
        'cleanup_after_days' => 90,

        // Default prompt template for citation checking
        'prompt_template' => "Please search for information about: %s\n\nProvide a comprehensive answer with relevant sources and citations.",
    ],

    /*
    |--------------------------------------------------------------------------
    | GA4 Sync Settings
    |--------------------------------------------------------------------------
    |
    | Settings for GA4 data synchronization.
    |
    */

    'ga4_sync' => [
        // Days of historical data to sync on initial setup
        'initial_sync_days' => 30,

        // Days of data to sync on regular updates
        'daily_sync_days' => 7,

        // Cleanup: delete referral data older than this many days
        'cleanup_after_days' => 365,
    ],

];
