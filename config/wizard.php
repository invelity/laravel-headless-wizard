<?php

declare(strict_types=1);

return [
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
    | Registered Wizards
    |--------------------------------------------------------------------------
    |
    | Register your wizards here. Each wizard should have a unique key,
    | a class, and an array of step classes in the order they should execute.
    |
    | Example:
    | 'onboarding' => [
    |     'class' => App\Wizards\Onboarding::class,
    |     'steps' => [
    |         App\Wizards\Steps\PersonalInfoStep::class,
    |         App\Wizards\Steps\PreferencesStep::class,
    |     ],
    | ],
    |
    */
    'wizards' => [
        // Auto-registered wizards will appear here
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

        /*
        | FormRequest Mapping (Optional)
        |
        | Override the default convention-based FormRequest discovery.
        | Convention: App\Wizards\Steps\PersonalInfoStep
        |          → App\Http\Requests\Wizards\PersonalInfoRequest
        |
        | Example:
        | 'form_requests' => [
        |     \App\Wizards\Steps\CustomStep::class => \App\Http\Requests\CustomFormRequest::class,
        | ],
        */
        'form_requests' => [
            // Add your custom Step → FormRequest mappings here
        ],
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
