<?php

return [
    // Keep at 0 for `php artisan serve` (one request at a time). Use ~25 behind php-fpm.
    'hold_seconds' => (int) env('SSE_HOLD_SECONDS', 0),
    'retry_ms' => (int) env('SSE_RETRY_MS', 2000),

    // task_events rows older than this are pruned by the scheduler.
    'event_retention_hours' => (int) env('SSE_EVENT_RETENTION_HOURS', 24),
];
