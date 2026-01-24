<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define all available subscription plans with features and limits.
    |
    */

    'plans' => [
        'user' => [
            'pro' => [
                'name' => 'Pro',
                'description' => 'For professionals who need comprehensive GEO analysis',
                'price_id' => env('STRIPE_PRICE_PRO', 'price_1SrTlpPAXrN2W8mrSqD4nLoj'),
                'price' => 39.00,
                'currency' => 'USD',
                'interval' => 'month',
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
                    'member_scans_per_month' => 25, // Per-member limit within team
                    'white_label' => false,
                    'scheduled_scans' => false,
                    'pdf_export' => true,
                    'bulk_scanning' => false,
                    // Citation tracking (Agency only)
                    'citation_queries' => 0,
                    'citation_checks_per_day' => 0,
                    'citation_frequency' => [],
                    'citation_platforms' => [],
                    'ga4_connections' => 0,
                ],
            ],
            'agency' => [
                'name' => 'Agency',
                'description' => 'For agencies managing multiple clients',
                'price_id' => env('STRIPE_PRICE_AGENCY'),
                'price' => 99.00,
                'currency' => 'USD',
                'interval' => 'month',
                'popular' => true,
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
                    'scans_per_month' => -1, // unlimited
                    'history_days' => -1, // unlimited
                    'teams_allowed' => 3,
                    'team_members' => 5,
                    'member_scans_per_month' => 100, // Per-member limit within team
                    'white_label' => true,
                    'scheduled_scans' => true,
                    'pdf_export' => true,
                    'bulk_scanning' => true,
                    // Citation tracking limits
                    'citation_queries' => 25,
                    'citation_checks_per_day' => 50,
                    'citation_frequency' => ['manual', 'daily', 'weekly'],
                    'citation_platforms' => ['perplexity', 'claude'],
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
    |
    */

    'free' => [
        'name' => 'Free',
        'features' => [
            '3 scans per month',
            'Basic GEO score',
            'Top 3 recommendations',
            '7-day scan history',
        ],
        'limits' => [
            'scans_per_month' => 3,
            'history_days' => 7,
            'teams_allowed' => 0,
            'team_members' => 0,
            'recommendations_shown' => 3,
            'white_label' => false,
            'scheduled_scans' => false,
            'pdf_export' => false,
            'bulk_scanning' => false,
            // Citation tracking limits (not available for free tier)
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

    'trial_days' => 14,

];
