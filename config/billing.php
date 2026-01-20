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
                    'Export to CSV',
                ],
                'limits' => [
                    'scans_per_month' => 50,
                    'history_days' => 90,
                    'teams_allowed' => 1,
                    'team_members' => 5,
                    'competitor_tracking' => 0,
                    'api_access' => false,
                    'white_label' => false,
                    'scheduled_scans' => false,
                    'pdf_export' => false,
                    'csv_export' => true,
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
                    'White-label reports',
                    'API access',
                    'Unlimited scan history',
                    'Competitor tracking (5 domains)',
                    'Custom branding',
                    'Dedicated support',
                    'Export to PDF & CSV',
                    'Scheduled scans',
                    'Bulk URL scanning',
                ],
                'limits' => [
                    'scans_per_month' => -1, // unlimited
                    'history_days' => -1, // unlimited
                    'teams_allowed' => 3,
                    'team_members' => 5,
                    'competitor_tracking' => 5,
                    'api_access' => true,
                    'white_label' => true,
                    'scheduled_scans' => true,
                    'pdf_export' => true,
                    'csv_export' => true,
                    'bulk_scanning' => true,
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
            'competitor_tracking' => 0,
            'recommendations_shown' => 3,
            'api_access' => false,
            'white_label' => false,
            'scheduled_scans' => false,
            'pdf_export' => false,
            'csv_export' => false,
            'bulk_scanning' => false,
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

    /*
    |--------------------------------------------------------------------------
    | Team Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for team-related billing settings.
    |
    */

    'team' => [
        'max_members' => [
            'pro' => 1,
            'agency' => 5,
        ],
    ],

];
