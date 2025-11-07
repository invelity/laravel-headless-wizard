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

it('omits isOptional parameter when false', function () {
    artisan('wizard:make-step TestWizard BasicStep --order=1')
        ->expectsQuestion('What is the step title?', 'Basic Step')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    $content = File::get(app_path('Wizards/TestWizardWizard/Steps/BasicStepStep.php'));

    expect($content)
        ->not->toContain('isOptional: false')
        ->not->toContain('isOptional:');
});

it('includes isOptional parameter when true', function () {
    artisan('wizard:make-step TestWizard OptionalStep --order=1 --optional=true')
        ->expectsQuestion('What is the step title?', 'Optional Step')
        ->execute();

    $content = File::get(app_path('Wizards/TestWizardWizard/Steps/OptionalStepStep.php'));

    expect($content)->toContain('isOptional: true');
});

it('omits canSkip parameter when false', function () {
    artisan('wizard:make-step TestWizard BasicStep --order=1')
        ->expectsQuestion('What is the step title?', 'Basic Step')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    $content = File::get(app_path('Wizards/TestWizardWizard/Steps/BasicStepStep.php'));

    expect($content)
        ->not->toContain('canSkip: false')
        ->not->toContain('canSkip:');
});

it('uses clean constructor with only required parameters', function () {
    artisan('wizard:make-step TestWizard CleanStep --order=1')
        ->expectsQuestion('What is the step title?', 'Clean Step')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->execute();

    $content = File::get(app_path('Wizards/TestWizardWizard/Steps/CleanStepStep.php'));

    expect($content)
        ->toContain("id: 'clean-step'")
        ->toContain("title: 'Clean Step'")
        ->toContain('order: 1');
});
