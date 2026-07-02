<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | Defaults to "*" to preserve legacy behavior. In production, set
    | CORS_ALLOWED_ORIGINS to a comma-separated allowlist of trusted
    | front-end origins (e.g. https://app.example.com,https://m.example.com)
    | to lock down which sites may call the API from a browser.
    */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
