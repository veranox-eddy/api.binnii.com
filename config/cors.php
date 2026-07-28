<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The parent SPA talks to this API with a Bearer token, so credentials
    | (cookies) are deliberately off — turning them on would require pinning
    | `allowed_origins` to an exact list anyway, and there is no session here
    | to protect.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(
        array_map(trim(...), explode(',', (string) env('FRONTEND_URL', ''))),
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
