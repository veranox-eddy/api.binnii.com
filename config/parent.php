<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parent SPA
    |--------------------------------------------------------------------------
    |
    | Where activation and password-reset links point. The API itself serves
    | no HTML — the SPA reads the token out of the query string and posts it
    | back to /api/v1/auth/*.
    |
    */

    'app_url' => rtrim((string) env('PARENT_APP_URL', env('FRONTEND_URL', 'http://localhost:5173')), '/'),

    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | Activation tokens are HMAC-signed rather than encrypted so the admin
    | console — a separate application with its own APP_KEY — can mint the
    | link it puts in the welcome email. Both apps must share
    | GUARDIAN_ACTIVATION_SECRET; without it each falls back to its own
    | APP_KEY and only tokens minted here will validate.
    |
    */

    'activation_secret' => env('GUARDIAN_ACTIVATION_SECRET', env('APP_KEY')),

    'activation_ttl_days' => (int) env('GUARDIAN_ACTIVATION_TTL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Passwords
    |--------------------------------------------------------------------------
    |
    | The parent portal requires 12 characters (API_03). Note this is stricter
    | than the admin console, which is still on Laravel's default of 8.
    |
    */

    'min_password_length' => 12,

];
