<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),

    /**
     * Write ANALYTICS_* to .env from admin UI. Default false.
     * Enabling this can restart Vite or reset php artisan serve on Windows.
     * Keys are always stored in analytics_sites; set ANALYTICS_SYNC_ENV=true only on cPanel if needed.
     */
    'sync_env' => filter_var(env('ANALYTICS_SYNC_ENV', false), FILTER_VALIDATE_BOOLEAN),

    'ingest' => [
        'rate_limit_per_minute' => (int) env('ANALYTICS_INGEST_RATE_LIMIT', 1200),
        'max_batch_size' => (int) env('ANALYTICS_MAX_BATCH_SIZE', 50),
        'max_payload_kb' => (int) env('ANALYTICS_MAX_PAYLOAD_KB', 256),
        'queue' => env('ANALYTICS_INGEST_QUEUE', 'analytics'),
        'redis_buffer_key' => env('ANALYTICS_REDIS_BUFFER', 'analytics:ingest:buffer'),
        'dedup_ttl_seconds' => (int) env('ANALYTICS_DEDUP_TTL', 86400),
        /** When false and Redis is up, use queue buffer (requires queue worker) */
        'sync_when_no_redis' => env('ANALYTICS_SYNC_WITHOUT_REDIS', true),
    ],

    /**
     * null = auto-detect (try Redis with fast fallback). Set ANALYTICS_USE_REDIS=false on hosts without Redis.
     */
    'use_redis' => env('ANALYTICS_USE_REDIS') === null
        ? null
        : filter_var(env('ANALYTICS_USE_REDIS'), FILTER_VALIDATE_BOOLEAN),

    'realtime' => [
        'redis_prefix' => env('ANALYTICS_REALTIME_PREFIX', 'analytics:rt'),
        'active_window_seconds' => (int) env('ANALYTICS_ACTIVE_WINDOW', 300),
        'poll_interval_ms' => (int) env('ANALYTICS_POLL_INTERVAL_MS', 5000),
    ],

    'tracker' => [
        'cdn_url' => env('ANALYTICS_TRACKER_URL', '/analytics/tracker.js'),
        'collect_url' => env('ANALYTICS_COLLECT_URL', null),
        'batch_size' => (int) env('ANALYTICS_TRACKER_BATCH_SIZE', 20),
        'flush_interval_ms' => (int) env('ANALYTICS_TRACKER_FLUSH_MS', 3000),
    ],

    'bot_user_agents' => [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit',
        'headless', 'phantom', 'selenium', 'wget', 'curl/',
    ],

    'future' => [
        'clickhouse_enabled' => env('ANALYTICS_CLICKHOUSE_ENABLED', false),
        'kafka_enabled' => env('ANALYTICS_KAFKA_ENABLED', false),
    ],
];
