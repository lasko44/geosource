<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define all available subscription plans. Each plan should have a unique
    | key that matches the Stripe Price ID. Plans can be for users or teams.
    |
    */

    'plans' => [
        'user' => [
            'pro' => [
                'name' => 'Pro',
                'description' => 'For professionals who need more power',
                'price_id' => env('STRIPE_PRICE_PRO'),
                'price' => 20.00,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    // Add your Pro features here
                ],
            ],
            'agency' => [
                'name' => 'Agency',
                'description' => 'For agencies and teams',
                'price_id' => env('STRIPE_PRICE_AGENCY'),
                'price' => 99.00,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    // Add your Agency features here
                ],
            ],
        ],

        'team' => [
            // Team plans can be added here if needed
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
            // Add team member limits here if needed
        ],
    ],

];
