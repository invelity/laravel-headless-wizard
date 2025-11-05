<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('MakeWizardCommand creates wizard successfully', function () {
    File::cleanDirectory(app_path('Wizards'));
    File::ensureDirectoryExists(app_path('Wizards'));

    $this->artisan('wizard:make', ['name' => 'TestWizard'])
        ->assertSuccessful();

    expect(File::exists(app_path('Wizards/TestWizardWizard/TestWizard.php')))->toBeTrue();
    expect(File::isDirectory(app_path('Wizards/TestWizardWizard/Steps')))->toBeTrue();

    File::cleanDirectory(app_path('Wizards'));
});
