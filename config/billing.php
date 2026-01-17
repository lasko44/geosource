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
            'basic' => [
                'name' => 'Basic',
                'description' => 'Perfect for individuals getting started',
                'price_id' => env('STRIPE_PRICE_BASIC', 'price_basic'),
                'price' => 9.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    '5 projects',
                    '1GB storage',
                    'Email support',
                ],
            ],
            'pro' => [
                'name' => 'Pro',
                'description' => 'For professionals who need more',
                'price_id' => env('STRIPE_PRICE_PRO', 'price_pro'),
                'price' => 29.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Unlimited projects',
                    '10GB storage',
                    'Priority support',
                    'Advanced analytics',
                ],
            ],
        ],

        'team' => [
            'team_starter' => [
                'name' => 'Team Starter',
                'description' => 'Great for small teams',
                'price_id' => env('STRIPE_PRICE_TEAM_STARTER', 'price_team_starter'),
                'price' => 49.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Up to 5 team members',
                    '25GB storage',
                    'Team collaboration',
                    'Priority support',
                ],
            ],
            'team_business' => [
                'name' => 'Team Business',
                'description' => 'For growing teams',
                'price_id' => env('STRIPE_PRICE_TEAM_BUSINESS', 'price_team_business'),
                'price' => 99.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Up to 25 team members',
                    '100GB storage',
                    'Advanced team features',
                    'Dedicated support',
                    'Custom integrations',
                ],
            ],
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
            'team_starter' => 5,
            'team_business' => 25,
        ],
    ],

];
