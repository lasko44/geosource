<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Email
    |--------------------------------------------------------------------------
    |
    | Email address of the platform admin account. Used by the AdminUserSeeder
    | to provision the admin user and by Nova resources to scope queries to
    | that user. Kept out of source so the value lives only in environment
    | configuration.
    |
    */

    'email' => env('ADMIN_EMAIL', 'admin@geosource.ai'),
];
