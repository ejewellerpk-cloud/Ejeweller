<?php

return [
    'enabled' => env('ANALYTICS_ENTERPRISE_ENABLED', true),

    'features' => [
        'heatmaps' => env('ANALYTICS_FEATURE_HEATMAPS', true),
        'session_replay' => env('ANALYTICS_FEATURE_REPLAY', true),
        'ai_insights' => env('ANALYTICS_FEATURE_AI_INSIGHTS', true),
        'cart_recovery' => env('ANALYTICS_FEATURE_CART_RECOVERY', true),
        'customer_journey' => env('ANALYTICS_FEATURE_JOURNEY', true),
        'segments' => env('ANALYTICS_FEATURE_SEGMENTS', true),
        'experiments' => env('ANALYTICS_FEATURE_EXPERIMENTS', true),
        'attribution' => env('ANALYTICS_FEATURE_ATTRIBUTION', true),
        'alerts' => env('ANALYTICS_FEATURE_ALERTS', true),
        'billing' => env('ANALYTICS_FEATURE_BILLING', false),
    ],

    'behavior_ingest' => [
        'rate_limit_per_minute' => (int) env('ANALYTICS_BEHAVIOR_RATE_LIMIT', 600),
        'max_batch_size' => (int) env('ANALYTICS_BEHAVIOR_MAX_BATCH', 100),
        'max_payload_kb' => (int) env('ANALYTICS_BEHAVIOR_MAX_KB', 512),
        'queue' => env('ANALYTICS_BEHAVIOR_QUEUE', 'analytics'),
    ],

    'heatmap' => [
        'grid_size' => 32,
        'raw_retention_days' => (int) env('ANALYTICS_HEATMAP_RETENTION_DAYS', 7),
    ],

    'replay' => [
        'chunk_seconds' => 30,
        'max_session_mb' => (int) env('ANALYTICS_REPLAY_MAX_MB', 5),
        'signed_url_ttl_minutes' => 15,
        'storage_disk' => env('ANALYTICS_REPLAY_DISK', 'local'),
    ],

    'insights' => [
        'schedule' => env('ANALYTICS_INSIGHTS_CRON', '0 */6 * * *'),
    ],

    'recovery' => [
        'default_delays_minutes' => [60, 1440, 4320],
    ],
];
