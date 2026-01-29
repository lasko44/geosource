<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Costs
    |--------------------------------------------------------------------------
    |
    | Define the token cost for each feature. Set to 0 for free features.
    |
    */

    'costs' => [
        // Scan Types
        'scan_basic' => 0,  // 5 pillars - FREE (lead gen)
        'scan_pro' => 5,    // 8 pillars
        'scan_full' => 10,  // 12 pillars

        // Citation Sources
        'citation_deepseek' => 1,
        'citation_gemini' => 2,
        'citation_perplexity' => 3,
        'citation_claude' => 3,
        'citation_google' => 2,
        'citation_youtube' => 2,
        'citation_facebook' => 2,
        'citation_chatgpt' => 5,

        // Other Features
        'pdf_export' => 2,
        'scheduled_scan' => 5, // per run
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Labels
    |--------------------------------------------------------------------------
    |
    | Human-readable labels for each feature (used in transaction history).
    |
    */

    'labels' => [
        'scan_basic' => 'Basic Scan (5 pillars)',
        'scan_pro' => 'Pro Scan (8 pillars)',
        'scan_full' => 'Full Scan (12 pillars)',
        'citation_deepseek' => 'DeepSeek Citation',
        'citation_gemini' => 'Gemini Citation',
        'citation_perplexity' => 'Perplexity Citation',
        'citation_claude' => 'Claude Citation',
        'citation_google' => 'Google Search Citation',
        'citation_youtube' => 'YouTube Citation',
        'citation_facebook' => 'Facebook Citation',
        'citation_chatgpt' => 'ChatGPT Citation',
        'pdf_export' => 'PDF Export',
        'scheduled_scan' => 'Scheduled Scan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Tier Mapping
    |--------------------------------------------------------------------------
    |
    | Map scan tiers to their token feature keys.
    |
    */

    'scan_tiers' => [
        'basic' => 'scan_basic',
        'pro' => 'scan_pro',
        'full' => 'scan_full',
        'FREE' => 'scan_basic',
        'PRO' => 'scan_pro',
        'AGENCY' => 'scan_full',
    ],

    /*
    |--------------------------------------------------------------------------
    | Citation Provider Mapping
    |--------------------------------------------------------------------------
    |
    | Map citation providers to their token feature keys.
    |
    */

    'citation_providers' => [
        'deepseek' => 'citation_deepseek',
        'gemini' => 'citation_gemini',
        'perplexity' => 'citation_perplexity',
        'claude' => 'citation_claude',
        'google' => 'citation_google',
        'youtube' => 'citation_youtube',
        'facebook' => 'citation_facebook',
        'openai' => 'citation_chatgpt', // Platform is called 'openai' but uses ChatGPT
    ],
];
