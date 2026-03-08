<?php

return [
    'enabled' => env('CLICKHOUSE_ENABLED', false),
    'host' => env('CLICKHOUSE_HOST', 'clickhouse'),
    'port' => env('CLICKHOUSE_PORT', 8123),
    'database' => env('CLICKHOUSE_DATABASE', 'default'),
    'user' => env('CLICKHOUSE_USER', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
];
