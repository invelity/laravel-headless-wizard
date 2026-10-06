<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Wizard Store
    |--------------------------------------------------------------------------
    |
    | The store that keeps the state of wizards which do not name their own.
    | The session store relies on the session belonging to one visitor; the
    | other stores keep a separate state for every visitor's scope.
    |
    */

    'default' => env('WIZARD_STORE', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Wizard Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define every store your wizards use. The database store
    | needs the "wizard_states" table, published with the migrations.
    |
    | Supported drivers: "session", "cache", "database", "array"
    |
    */

    'stores' => [

        'session' => [
            'driver' => 'session',
            'prefix' => 'wizard_',
        ],

        'cache' => [
            'driver' => 'cache',
            'store' => env('WIZARD_CACHE_STORE'),
            'prefix' => 'wizard:',
            'ttl' => 60 * 60 * 24,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('WIZARD_DB_CONNECTION'),
            'table' => 'wizard_states',
            'encrypt' => true,
        ],

        'array' => [
            'driver' => 'array',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Define which storage backend to use for wizard state persistence.
    | Supported: "session", "database", "cache"
    |
    */
    'storage' => [
        'driver' => env('WIZARD_STORAGE', 'session'),
        'ttl' => 3600, // Cache TTL in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => true,
        'prefix' => env('WIZARD_ROUTE_PREFIX', 'wizard'),
        'middleware' => ['web', 'wizard.session'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Storage Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        'key' => 'wizard_data',
        'lifetime' => 120, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Storage Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'table' => 'wizard_progress',
        'connection' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Storage Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => env('WIZARD_CACHE_DRIVER', 'redis'),
        'ttl' => 7200, // seconds (2 hours)
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Settings
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'allow_jump' => false, // Allow direct navigation to any accessible step
        'show_all_steps' => true, // Show all steps in navigation
        'mark_completed' => true, // Mark completed steps visually
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'validate_on_navigate' => true, // Validate when navigating back
        'allow_skip_optional' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    */
    'events' => [
        'dispatch' => true, // Enable/disable event dispatching
        'log_progress' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'abandoned_after_days' => 30,
        'auto_cleanup' => false, // Enable scheduled cleanup
    ],
];
