<?php

return [
    'enabled' => true,

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '0',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(self), fullscreen=(self)',
    ],

    'hsts' => [
        'enabled' => true,
        'max_age' => 31536000,
        'include_subdomains' => true,
        'preload' => false,
    ],

    'csp' => [
        'enabled' => true,
        'report_only' => false,
        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'object-src' => ["'none'"],
            'form-action' => ["'self'", 'https:'],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https:'],
            'style-src' => ["'self'", "'unsafe-inline'", 'https:'],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:', 'https:'],
            'connect-src' => ["'self'", 'https:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'frame-src' => ["'self'", 'https:'],
            'worker-src' => ["'self'", 'blob:'],
        ],
    ],

    'route_overrides' => [
        'live/*' => [
            'headers' => [
                'Permissions-Policy' => 'camera=(self), microphone=(self), geolocation=(), payment=(self), fullscreen=(self)',
            ],
            'csp' => [
                'directives' => [
                    'connect-src' => ["'self'", 'https:', 'wss:'],
                    'media-src' => ["'self'", 'blob:', 'https:'],
                    'worker-src' => ["'self'", 'blob:'],
                ],
            ],
        ],
    ],
];
