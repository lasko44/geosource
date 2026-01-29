<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define all available subscription plans with features and limits.
    | Note: Most features are now token-based (pay as you go).
    | The Team plan is for agencies needing team features + monthly tokens.
    |
    */

    'plans' => [
        'user' => [
            'team' => [
                'name' => 'Team',
                'description' => 'For agencies and growing teams',
                'price_id' => env('STRIPE_PRICE_TEAM', 'price_team_placeholder'),
                'price' => 99.00,
                'currency' => 'USD',
                'interval' => 'month',
                'popular' => true,
                'features' => [
                    '1,000 tokens included monthly',
                    '3 teams with 5 members each',
                    'White-label reports',
                    'GA4 AI Traffic Analytics',
                    'Unlimited scan history',
                    'Priority support',
                    'All token features included',
                ],
                'limits' => [
                    'monthly_tokens' => 1000, // Tokens credited each month
                    'scans_per_month' => -1, // unlimited (uses tokens)
                    'history_days' => -1, // unlimited
                    'teams_allowed' => 3,
                    'team_members' => 5,
                    'member_scans_per_month' => -1, // unlimited (uses tokens)
                    'white_label' => true,
                    'scheduled_scans' => true,
                    'pdf_export' => true,
                    'bulk_scanning' => true,
                    // Citation tracking (unlimited, uses tokens)
                    'citation_queries' => -1,
                    'citation_checks_per_day' => -1,
                    'citation_frequency' => ['manual', 'daily', 'weekly'],
                    'citation_platforms' => [
                        // AI Platforms
                        'perplexity', 'openai', 'claude', 'gemini', 'deepseek',
                        // Search/Social Platforms
                        'google', 'youtube', 'facebook',
                    ],
                    'ga4_connections' => 3,
                ],
            ],
            // Legacy plans kept for existing subscribers (hidden from new signups)
            'pro' => [
                'name' => 'Pro (Legacy)',
                'description' => 'Legacy plan - no longer available for new signups',
                'price_id' => env('STRIPE_PRICE_PRO', 'price_1SrTlpPAXrN2W8mrSqD4nLoj'),
                'price' => 39.00,
                'currency' => 'USD',
                'interval' => 'month',
                'hidden' => true, // Hide from pricing pages
                'features' => [
                    '50 scans per month',
                    'Full GEO score breakdown',
                    'All optimization recommendations',
                    '90-day scan history',
                    '1 team with 5 members',
                    'Email reports',
                    'Priority support',
                    'Export to PDF',
                ],
                'limits' => [
                    'scans_per_month' => 50,
                    'history_days' => 90,
                    'teams_allowed' => 1,
                    'team_members' => 5,
                    'member_scans_per_month' => 25,
                    'white_label' => false,
                    'scheduled_scans' => false,
                    'pdf_export' => true,
                    'bulk_scanning' => false,
                    'citation_queries' => 0,
                    'citation_checks_per_day' => 0,
                    'citation_frequency' => [],
                    'citation_platforms' => [],
                    'ga4_connections' => 0,
                ],
            ],
            'agency' => [
                'name' => 'Agency (Legacy)',
                'description' => 'Legacy plan - migrated to Team plan',
                'price_id' => env('STRIPE_PRICE_AGENCY'),
                'price' => 99.00,
                'currency' => 'USD',
                'interval' => 'month',
                'hidden' => true, // Hide from pricing pages
                'features' => [
                    'Unlimited scans',
                    'Everything in Pro',
                    '3 teams with 5 members each',
                    'White-label reports (team owners)',
                    'Unlimited scan history',
                    'Custom branding',
                    'Dedicated support',
                    'Export to PDF',
                    'Scheduled scans',
                    'Bulk URL scanning',
                    'Citation Tracking (25 queries)',
                    'GA4 AI Traffic Analytics',
                ],
                'limits' => [
                    'scans_per_month' => -1,
                    'history_days' => -1,
                    'teams_allowed' => 3,
                    'team_members' => 5,
                    'member_scans_per_month' => 100,
                    'white_label' => true,
                    'scheduled_scans' => true,
                    'pdf_export' => true,
                    'bulk_scanning' => true,
                    'citation_queries' => 25,
                    'citation_checks_per_day' => 100,
                    'citation_frequency' => ['manual', 'daily', 'weekly'],
                    'citation_platforms' => [
                        'perplexity', 'openai', 'claude', 'gemini', 'deepseek',
                        'google', 'youtube', 'facebook',
                    ],
                    'ga4_connections' => 3,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Tier Limits
    |--------------------------------------------------------------------------
    |
    | Limits for users without a subscription.
    | Free users can do unlimited basic scans (5 pillars).
    | Pro/Full scans and other features require tokens.
    |
    */

    'free' => [
        'name' => 'Free',
        'features' => [
            'Unlimited basic scans (5 pillars)',
            'Basic GEO score (100 pts max)',
            'Top 3 recommendations',
            '7-day scan history',
        ],
        'limits' => [
            'scans_per_month' => -1, // Unlimited basic scans
            'basic_scans_only' => true, // Only 5-pillar scans are free
            'history_days' => 7,
            'teams_allowed' => 0,
            'team_members' => 0,
            'recommendations_shown' => 3,
            'white_label' => false,
            'scheduled_scans' => false,
            'pdf_export' => false, // Requires tokens
            'bulk_scanning' => false,
            // Citation tracking (requires tokens)
            'citation_queries' => 0,
            'citation_checks_per_day' => 0,
            'citation_frequency' => [],
            'citation_platforms' => [],
            'ga4_connections' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | The number of days for the trial period.
    |
    */

    'trial_days' => 0, // No trial - use free tier + tokens instead

];
