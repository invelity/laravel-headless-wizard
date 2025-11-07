<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    if (File::exists(app_path('Wizards/TestWizard'))) {
        File::deleteDirectory(app_path('Wizards/TestWizard'));
    }
    if (File::exists(app_path('Wizards/TestWizardWizard'))) {
        File::deleteDirectory(app_path('Wizards/TestWizardWizard'));
    }
    if (File::exists(app_path('Http/Controllers/TestWizardController.php'))) {
        File::delete(app_path('Http/Controllers/TestWizardController.php'));
    }
    if (File::exists(app_path('Http/Requests/Wizards'))) {
        File::deleteDirectory(app_path('Http/Requests/Wizards'));
    }

    artisan('wizard:make TestWizard --type=blade')->execute();
});

afterEach(function () {
    if (File::exists(app_path('Wizards/TestWizard'))) {
        File::deleteDirectory(app_path('Wizards/TestWizard'));
    }
    if (File::exists(app_path('Wizards/TestWizardWizard'))) {
        File::deleteDirectory(app_path('Wizards/TestWizardWizard'));
    }
    if (File::exists(app_path('Http/Controllers/TestWizardController.php'))) {
        File::delete(app_path('Http/Controllers/TestWizardController.php'));
    }
    if (File::exists(app_path('Http/Requests/Wizards'))) {
        File::deleteDirectory(app_path('Http/Requests/Wizards'));
    }
});

it('inserts step at specified order and reorders existing steps', function () {
    artisan('wizard:make-step TestWizard Step1 --order=1')
        ->expectsQuestion('What is the step title?', 'Step 1')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard OldStep2 --order=2')
        ->expectsQuestion('What is the step title?', 'Old Step 2')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard NewStep2 --order=2')
        ->expectsQuestion('What is the step title?', 'New Step 2')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    $step1Content = File::get(app_path('Wizards/TestWizardWizard/Steps/Step1Step.php'));
    $newStep2Content = File::get(app_path('Wizards/TestWizardWizard/Steps/NewStep2Step.php'));
    $oldStep2Content = File::get(app_path('Wizards/TestWizardWizard/Steps/OldStep2Step.php'));

    expect($step1Content)->toContain('order: 1');
    expect($newStep2Content)->toContain('order: 2');

    expect($oldStep2Content)
        ->toMatch('/order:\s*[23]/');
});

it('automatically assigns next order when order not specified', function () {
    artisan('wizard:make-step TestWizard Step1 --order=1')
        ->expectsQuestion('What is the step title?', 'Step 1')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard Step2 --order=2')
        ->expectsQuestion('What is the step title?', 'Step 2')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard Step3')
        ->expectsQuestion('What is the step title?', 'Step 3')
        ->expectsQuestion('What is the step order?', '3')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    $step3Content = File::get(app_path('Wizards/TestWizardWizard/Steps/Step3Step.php'));
    expect($step3Content)->toContain('order: 3');
});

it('handles reordering with gaps in step numbers', function () {
    artisan('wizard:make-step TestWizard Step1 --order=1')
        ->expectsQuestion('What is the step title?', 'Step 1')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard Step5 --order=5')
        ->expectsQuestion('What is the step title?', 'Step 5')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    artisan('wizard:make-step TestWizard Step3 --order=3')
        ->expectsQuestion('What is the step title?', 'Step 3')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    expect(app_path('Wizards/TestWizardWizard/Steps/Step1Step.php'))->toBeFile();
    expect(app_path('Wizards/TestWizardWizard/Steps/Step3Step.php'))->toBeFile();
    expect(app_path('Wizards/TestWizardWizard/Steps/Step5Step.php'))->toBeFile();
});
