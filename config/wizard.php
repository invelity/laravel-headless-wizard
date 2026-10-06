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

];
