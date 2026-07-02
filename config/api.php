<?php

return [
    'idempotency' => [
        'ttl_minutes' => (int) env('API_IDEMPOTENCY_TTL_MINUTES', 60 * 24),
        'lock_seconds' => (int) env('API_IDEMPOTENCY_LOCK_SECONDS', 10),
        'wait_seconds' => (int) env('API_IDEMPOTENCY_WAIT_SECONDS', 3),
    ],
];
