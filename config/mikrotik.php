<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mikrotik Connection
    |--------------------------------------------------------------------------
    |
    | Default mikrotik device ID to be used when not specified
    |
    */
    'default_device_id' => env('MIKROTIK_DEFAULT_DEVICE_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Default connection settings for Mikrotik RouterOS API
    |
    */
    'connection' => [
        'timeout' => env('MIKROTIK_TIMEOUT', 5),
        'attempts' => env('MIKROTIK_ATTEMPTS', 3),
        'delay' => env('MIKROTIK_DELAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Isolir Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic customer isolation when expired
    |
    */
    'auto_isolir' => [
        'enabled' => env('MIKROTIK_AUTO_ISOLIR_ENABLED', true),
        'profile_name' => env('MIKROTIK_ISOLIR_PROFILE', 'ISOLIR'),
        'queue_name' => env('MIKROTIK_ISOLIR_QUEUE', 'ISOLIR'),
        'check_interval' => env('MIKROTIK_CHECK_INTERVAL', 60), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring mikrotik devices
    |
    */
    'monitoring' => [
        'enabled' => env('MIKROTIK_MONITORING_ENABLED', true),
        'interval' => env('MIKROTIK_MONITORING_INTERVAL', 5), // minutes
        'store_days' => env('MIKROTIK_MONITORING_STORE_DAYS', 30), // days to keep monitoring data
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Settings for syncing data between application and mikrotik
    |
    */
    'sync' => [
        'auto_sync' => env('MIKROTIK_AUTO_SYNC', false),
        'sync_on_create' => env('MIKROTIK_SYNC_ON_CREATE', true),
        'sync_on_update' => env('MIKROTIK_SYNC_ON_UPDATE', true),
        'sync_on_delete' => env('MIKROTIK_SYNC_ON_DELETE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | PPPoE Settings
    |--------------------------------------------------------------------------
    |
    | Settings for PPPoE service
    |
    */
    'pppoe' => [
        'default_service' => env('MIKROTIK_PPPOE_SERVICE', 'pppoe-server1'),
        'local_address' => env('MIKROTIK_PPPOE_LOCAL_ADDRESS', '10.10.10.1'),
        'remote_address_pool' => env('MIKROTIK_PPPOE_REMOTE_POOL', 'pool-pppoe'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Settings for Queue (bandwidth management)
    |
    */
    'queue' => [
        'type' => env('MIKROTIK_QUEUE_TYPE', 'simple'), // simple or tree
        'parent' => env('MIKROTIK_QUEUE_PARENT', 'none'),
        'max_limit' => env('MIKROTIK_QUEUE_MAX_LIMIT', '100M/100M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Enable/disable logging for mikrotik operations
    |
    */
    'logging' => [
        'enabled' => env('MIKROTIK_LOGGING_ENABLED', true),
        'channel' => env('MIKROTIK_LOGGING_CHANNEL', 'daily'),
        'log_api_calls' => env('MIKROTIK_LOG_API_CALLS', false),
    ],
];

