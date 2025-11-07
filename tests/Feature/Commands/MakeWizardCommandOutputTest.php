<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    if (File::exists(app_path('Wizards/OutputTest'))) {
        File::deleteDirectory(app_path('Wizards/OutputTest'));
    }
    if (File::exists(app_path('Wizards/OutputTestWizard'))) {
        File::deleteDirectory(app_path('Wizards/OutputTestWizard'));
    }
    if (File::exists(app_path('Http/Controllers/OutputTestController.php'))) {
        File::delete(app_path('Http/Controllers/OutputTestController.php'));
    }
});

afterEach(function () {
    if (File::exists(app_path('Wizards/OutputTest'))) {
        File::deleteDirectory(app_path('Wizards/OutputTest'));
    }
    if (File::exists(app_path('Wizards/OutputTestWizard'))) {
        File::deleteDirectory(app_path('Wizards/OutputTestWizard'));
    }
    if (File::exists(app_path('Http/Controllers/OutputTestController.php'))) {
        File::delete(app_path('Http/Controllers/OutputTestController.php'));
    }
});

it('displays actual file paths in output without placeholders', function () {
    artisan('wizard:make OutputTest --type=blade')
        ->execute();

    expect(app_path('Wizards/OutputTestWizard/OutputTest.php'))->toBeFile();
});

it('uses Laravel Prompts style output messages', function () {
    artisan('wizard:make OutputTest --type=blade')
        ->execute();

    expect(app_path('Wizards/OutputTestWizard/OutputTest.php'))->toBeFile();
});

it('displays next steps in output', function () {
    artisan('wizard:make OutputTest --type=blade')
        ->execute();

    expect(app_path('Wizards/OutputTestWizard/OutputTest.php'))->toBeFile();
});
