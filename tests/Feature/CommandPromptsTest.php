<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
    File::ensureDirectoryExists(app_path('Wizards'));
    File::ensureDirectoryExists(app_path('Http/Requests/Wizards'));

    $configPath = config_path('wizard-package.php');
    $configDir = dirname($configPath);

    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    $config = [
        'storage' => ['driver' => 'session'],
        'wizards' => [
            'checkout' => ['steps' => []],
            'onboarding' => ['steps' => []],
        ],
    ];

    File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
    
    config(['wizard-package.wizards' => $config['wizards']]);
});

afterEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
});

test('MakeStepCommand prompts for wizard selection when option not provided', function () {
    $wizards = config('wizard-package.wizards');
    expect($wizards)->toBeArray();
    expect($wizards)->not->toBeEmpty();
    
    if (empty($wizards)) {
        $this->markTestSkipped('Config not loaded properly in CI environment');
    }
    
    $this->artisan('wizard:make-step', [
        'name' => 'UserInfo',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('Which wizard should this step belong to?', 'checkout')
        ->expectsQuestion('What is the step title?', 'User Information')
        ->assertSuccessful();

    expect(File::exists(app_path('Wizards/Steps/UserInfoStep.php')))->toBeTrue();
});

test('MakeStepCommand validates empty step name', function () {
    $result = $this->artisan('wizard:make-step', [
        'name' => '',
        '--wizard' => 'checkout',
    ]);

    $result->assertFailed();
    expect($result->run())->toBe(1);
});

test('MakeStepCommand getLastStepOrder returns correct count', function () {
    $configPath = config_path('wizard-package.php');

    $config = [
        'storage' => ['driver' => 'session'],
        'wizards' => [
            'checkout' => [
                'steps' => [
                    'App\\Wizards\\Steps\\Step1',
                    'App\\Wizards\\Steps\\Step2',
                    'App\\Wizards\\Steps\\Step3',
                ],
            ],
        ],
    ];

    File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");

    $this->artisan('wizard:make-step', [
        'name' => 'Step4',
        '--wizard' => 'checkout',
    ])
        ->expectsQuestion('What is the step title?', 'Step 4')
        ->expectsQuestion('What is the step order?', '4')
        ->expectsConfirmation('Is this step optional?', false)
        ->assertSuccessful();

    $updatedConfig = require $configPath;
    expect($updatedConfig['wizards']['checkout']['steps'])->toHaveCount(4);
    expect($updatedConfig['wizards']['checkout']['steps'][3])->toBe('App\\Wizards\\Steps\\Step4Step');
});

test('MakeStepCommand handles step name validation errors', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'invalid-name',
        '--wizard' => 'checkout',
    ])
        ->assertFailed();

    expect(File::exists(app_path('Wizards/Steps/invalid-nameStep.php')))->toBeFalse();
});
