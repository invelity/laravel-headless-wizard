<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    if (File::exists(app_path('Wizards'))) {
        File::deleteDirectory(app_path('Wizards'));
    }

    if (File::exists(app_path('Http/Requests/Wizards'))) {
        File::deleteDirectory(app_path('Http/Requests/Wizards'));
    }
    
    $configPath = config_path('wizard-package.php');
    $configDir = dirname($configPath);

    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    $config = [
        'storage' => ['driver' => 'session', 'ttl' => 3600],
        'wizards' => [
            'test-wizard' => [
                'class' => 'App\Wizards\TestWizard',
                'steps' => [],
            ],
        ],
        'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
    ];

    File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
    
    $loadedConfig = require $configPath;
    config(['wizard-package' => $loadedConfig]);
});

afterEach(function () {
    if (File::exists(app_path('Wizards'))) {
        File::deleteDirectory(app_path('Wizards'));
    }

    if (File::exists(app_path('Http/Requests/Wizards'))) {
        File::deleteDirectory(app_path('Http/Requests/Wizards'));
    }
});

test('form request has rules method', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'UserInfo',
        '--wizard' => 'test-wizard',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'User Information')
        ->assertSuccessful();

    $requestPath = app_path('Http/Requests/Wizards/UserInfoRequest.php');

    require_once $requestPath;

    $requestClass = 'App\Http\Requests\Wizards\UserInfoRequest';
    expect(method_exists($requestClass, 'rules'))->toBeTrue();
    expect(method_exists($requestClass, 'authorize'))->toBeTrue();
});

test('form request authorize defaults to true', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'ProfileInfo',
        '--wizard' => 'test-wizard',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'Profile Information')
        ->assertSuccessful();

    $requestPath = app_path('Http/Requests/Wizards/ProfileInfoRequest.php');
    $requestContent = File::get($requestPath);

    expect($requestContent)->toContain('return true;');
});

test('form request rules returns array', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'ContactDetails',
        '--wizard' => 'test-wizard',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'Contact Details')
        ->assertSuccessful();

    $requestPath = app_path('Http/Requests/Wizards/ContactDetailsRequest.php');

    require_once $requestPath;

    $requestClass = 'App\Http\Requests\Wizards\ContactDetailsRequest';
    $request = new $requestClass;

    expect($request->rules())->toBeArray();
});

test('form request extends laravel form request', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'AddressInfo',
        '--wizard' => 'test-wizard',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'Address Information')
        ->assertSuccessful();

    $requestPath = app_path('Http/Requests/Wizards/AddressInfoRequest.php');

    require_once $requestPath;

    $requestClass = 'App\Http\Requests\Wizards\AddressInfoRequest';

    expect(is_subclass_of($requestClass, 'Illuminate\Foundation\Http\FormRequest'))->toBeTrue();
});
