<?php

return [
    'navigation_group' => env('FILAMENT_OPERATIONS_NAVIGATION_GROUP', 'System'),

    'backups' => [
        'enabled' => env('FILAMENT_OPERATIONS_BACKUPS_ENABLED', false),
        'disk' => env('FILAMENT_OPERATIONS_BACKUPS_DISK'),
        'path' => env('FILAMENT_OPERATIONS_BACKUPS_PATH', 'backups'),
        'extensions' => ['zip', 'sql.gz'],
        'download_ttl_minutes' => (int) env('FILAMENT_OPERATIONS_BACKUPS_DOWNLOAD_TTL', 60),
    ],

    'logs' => [
        'enabled' => env('FILAMENT_OPERATIONS_LOGS_ENABLED', true),
        'roots' => [
            [
                'label' => env('FILAMENT_OPERATIONS_LOGS_LABEL', 'Application logs'),
                'path' => env('FILAMENT_OPERATIONS_LOGS_PATH', storage_path('logs')),
            ],
        ],
        'max_preview_bytes' => (int) env('FILAMENT_OPERATIONS_LOGS_MAX_PREVIEW_BYTES', 1_048_576),
        'max_lines' => (int) env('FILAMENT_OPERATIONS_LOGS_MAX_LINES', 2_000),
        'allow_clear' => env('FILAMENT_OPERATIONS_LOGS_ALLOW_CLEAR', false),
        'allow_delete' => env('FILAMENT_OPERATIONS_LOGS_ALLOW_DELETE', false),
    ],

    'download_route' => [
        'prefix' => env('FILAMENT_OPERATIONS_DOWNLOAD_PREFIX', 'filament-operations'),
        'middleware' => ['web', 'auth', 'signed'],
    ],
];
