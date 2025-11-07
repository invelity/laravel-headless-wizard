<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    if (File::exists(app_path('Wizards/TypeTest'))) {
        File::deleteDirectory(app_path('Wizards/TypeTest'));
    }
    if (File::exists(app_path('Wizards/TypeTestWizard'))) {
        File::deleteDirectory(app_path('Wizards/TypeTestWizard'));
    }
    if (File::exists(app_path('Http/Controllers/TypeTestController.php'))) {
        File::delete(app_path('Http/Controllers/TypeTestController.php'));
    }
});

afterEach(function () {
    if (File::exists(app_path('Wizards/TypeTest'))) {
        File::deleteDirectory(app_path('Wizards/TypeTest'));
    }
    if (File::exists(app_path('Wizards/TypeTestWizard'))) {
        File::deleteDirectory(app_path('Wizards/TypeTestWizard'));
    }
    if (File::exists(app_path('Http/Controllers/TypeTestController.php'))) {
        File::delete(app_path('Http/Controllers/TypeTestController.php'));
    }
});

it('generates wizard class for any type', function () {
    artisan('wizard:make TypeTest --type=blade')
        ->execute();

    expect(app_path('Wizards/TypeTestWizard/TypeTest.php'))->toBeFile();
    expect(app_path('Http/Controllers/TypeTestController.php'))->toBeFile();
});

// These tests will be enhanced once wizard type selection is implemented
it('will support wizard type selection in the future', function () {
    // Placeholder test - type selection will be interactive
    // This test documents the intended behavior
    expect(true)->toBeTrue();
})->todo();

it('will display CSRF instructions for API wizards in the future', function () {
    // Placeholder test - CSRF instructions for non-Blade types
    // This test documents the intended behavior
    expect(true)->toBeTrue();
})->todo();
