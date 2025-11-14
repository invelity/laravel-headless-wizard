<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
    File::ensureDirectoryExists(app_path('Wizards'));
    File::ensureDirectoryExists(app_path('Http/Requests/Wizards'));

    $configPath = config_path('wizard.php');
    $configDir = dirname($configPath);

    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    $config = [
        'storage' => ['driver' => 'session'],
    ];

    File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");

    File::makeDirectory(app_path('Wizards/CheckoutWizard'), 0755, true);
    File::put(app_path('Wizards/CheckoutWizard/Checkout.php'), "<?php\n\nnamespace App\\Wizards\\CheckoutWizard;\n\nclass Checkout {\n    public function getId(): string { return 'checkout'; }\n}\n");

    File::makeDirectory(app_path('Wizards/OnboardingWizard'), 0755, true);
    File::put(app_path('Wizards/OnboardingWizard/Onboarding.php'), "<?php\n\nnamespace App\\Wizards\\OnboardingWizard;\n\nclass Onboarding {\n    public function getId(): string { return 'onboarding'; }\n}\n");
});

afterEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
});

test('MakeStepCommand creates step when all arguments provided', function () {
    $this->artisan('wizard:make-step', [
        'wizard' => 'Checkout',
        'name' => 'UserInfo',
        '--order' => 1,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'User Information')
        ->execute();

    expect(File::exists(app_path('Wizards/CheckoutWizard/Steps/UserInfoStep.php')))->toBeTrue();
});

test('MakeStepCommand validates empty step name', function () {
    $result = $this->artisan('wizard:make-step', [
        'wizard' => 'Checkout',
        'name' => '',
    ]);

    $result->assertFailed();
    expect($result->run())->toBe(1);
});

test('MakeStepCommand getLastStepOrder returns correct count', function () {
    File::makeDirectory(app_path('Wizards/CheckoutWizard/Steps'), 0755, true);
    File::put(app_path('Wizards/CheckoutWizard/Steps/Step1Step.php'), "<?php\n\nnamespace App\\Wizards\\CheckoutWizard\\Steps;\n\nclass Step1Step {}\n");
    File::put(app_path('Wizards/CheckoutWizard/Steps/Step2Step.php'), "<?php\n\nnamespace App\\Wizards\\CheckoutWizard\\Steps;\n\nclass Step2Step {}\n");
    File::put(app_path('Wizards/CheckoutWizard/Steps/Step3Step.php'), "<?php\n\nnamespace App\\Wizards\\CheckoutWizard\\Steps;\n\nclass Step3Step {}\n");

    $this->artisan('wizard:make-step', [
        'wizard' => 'Checkout',
        'name' => 'Step4',
        '--order' => 4,
        '--optional' => false,
    ])
        ->expectsQuestion('What is the step title?', 'Step 4')
        ->execute();

    expect(File::exists(app_path('Wizards/CheckoutWizard/Steps/Step4Step.php')))->toBeTrue();
    $files = File::files(app_path('Wizards/CheckoutWizard/Steps'));
    expect(count($files))->toBe(4);
});

test('MakeStepCommand handles step name validation errors', function () {
    $this->artisan('wizard:make-step', [
        'wizard' => 'Checkout',
        'name' => 'invalid-name',
    ])
        ->assertFailed();

    expect(File::exists(app_path('Wizards/CheckoutWizard/Steps/invalid-nameStep.php')))->toBeFalse();
});
