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

it('generates wizard with correct file structure', function () {
    artisan('wizard:make TestWizard --type=blade')
        ->execute();

    expect(app_path('Wizards/TestWizardWizard/TestWizard.php'))->toBeFile();
    expect(app_path('Wizards/TestWizardWizard/Steps'))->toBeDirectory();
    expect(app_path('Http/Controllers/TestWizardController.php'))->toBeFile();
});

it('creates wizard class with correct namespace', function () {
    artisan('wizard:make TestWizard --type=blade')
        ->execute();

    $content = File::get(app_path('Wizards/TestWizardWizard/TestWizard.php'));

    expect($content)
        ->toContain('namespace App\Wizards\TestWizardWizard')
        ->toContain('class TestWizard')
        ->toContain('declare(strict_types=1)');
});

it('validates wizard name is PascalCase', function () {
    artisan('wizard:make lowercase-wizard')
        ->assertFailed();

    expect(app_path('Wizards/LowercaseWizard'))->not->toBeDirectory();
});

it('refuses to overwrite existing wizard without force flag', function () {
    artisan('wizard:make TestWizard --type=blade')->execute();

    artisan('wizard:make TestWizard --type=blade')
        ->assertFailed();
});

it('overwrites existing wizard with force flag', function () {
    artisan('wizard:make TestWizard --type=blade')->execute();

    artisan('wizard:make TestWizard --type=blade --force')
        ->execute();

    expect(app_path('Wizards/TestWizardWizard/TestWizard.php'))->toBeFile();
});
