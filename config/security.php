<?php

return [
    'server_side_url' => [
        'allowed_hosts' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('SERVER_SIDE_URL_ALLOWED_HOSTS', '*'))),
            fn (string $host): bool => $host !== ''
        )),
        'allowed_schemes' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('SERVER_SIDE_URL_ALLOWED_SCHEMES', 'http,https'))),
            fn (string $scheme): bool => $scheme !== ''
        )),
        'timeout_seconds' => (int) env('SERVER_SIDE_URL_TIMEOUT_SECONDS', 5),
        'max_redirects' => (int) env('SERVER_SIDE_URL_MAX_REDIRECTS', 0),
        'max_response_bytes' => (int) env('SERVER_SIDE_URL_MAX_RESPONSE_BYTES', 1048576),
        'user_agent' => env('SERVER_SIDE_URL_USER_AGENT', 'SocioproLinkPreview/1.0'),
    ],

    'webhooks' => [
        'paystack' => [
            'signature_header' => 'X-Paystack-Signature',
            'timestamp_header' => 'X-Sociopro-Timestamp',
            'require_timestamp' => filter_var(env('PAYSTACK_WEBHOOK_REQUIRE_TIMESTAMP', false), FILTER_VALIDATE_BOOL),
            'timestamp_tolerance_seconds' => (int) env('PAYSTACK_WEBHOOK_TIMESTAMP_TOLERANCE_SECONDS', 300),
            'idempotency_header' => 'X-Paystack-Event',
            'replay_ttl_seconds' => (int) env('PAYSTACK_WEBHOOK_REPLAY_TTL_SECONDS', 86400),
        ],
    ],
];
