<?php

declare(strict_types=1);

return [
    'slow_query_ms' => (int) env('SLOW_QUERY_MS', 200),
    'api_rate_per_minute' => (int) env('API_RATE_PER_MINUTE', 60),
    'partner_rate_per_minute' => (int) env('PARTNER_RATE_PER_MINUTE', 30),
    'outbox_nudge' => filter_var(env('OUTBOX_NUDGE', true), FILTER_VALIDATE_BOOL),
    'outbox_batch' => (int) env('OUTBOX_BATCH', 50),
    'outbox_max_attempts' => (int) env('OUTBOX_MAX_ATTEMPTS', 10),
    'outbox_lease_seconds' => (int) env('OUTBOX_LEASE_SECONDS', 60),
    'metrics_token' => env('METRICS_TOKEN'),
    'query_budget' => (int) env('QUERY_BUDGET', 15),
];
