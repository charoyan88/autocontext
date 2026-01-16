<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Noise Levels
    |--------------------------------------------------------------------------
    |
    | Log levels that should be filtered out as noise.
    |
    */
    'noise_levels' => [
        'DEBUG',
        'TRACE',
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Paths
    |--------------------------------------------------------------------------
    |
    | HTTP paths that should be filtered out (health checks, monitoring).
    |
    */
    'health_check_paths' => [
        '/health',
        '/ping',
        '/metrics',
        '/status',
        '/healthz',
        '/readiness',
        '/liveness',
        '/_health',
        '/_status',
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate Error Threshold
    |--------------------------------------------------------------------------
    |
    | Maximum number of identical errors allowed per minute before filtering.
    |
    */
    'duplicate_error_threshold' => 10,

    /*
    |--------------------------------------------------------------------------
    | Deployment Window Minutes
    |--------------------------------------------------------------------------
    |
    | Time window in minutes after deployment to mark events as deployment-related.
    |
    */
    'deployment_window_minutes' => 30,
];
