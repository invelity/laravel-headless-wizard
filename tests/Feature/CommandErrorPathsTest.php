<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('MakeWizardCommand exception handler shows error when config missing', function () {
    File::cleanDirectory(app_path('Wizards'));
    File::ensureDirectoryExists(app_path('Wizards'));

    $configPath = config_path('wizard-package.php');
    $backupPath = $configPath.'.backup_test';

    if (File::exists($configPath)) {
        File::move($configPath, $backupPath);
    }

    try {
        $this->artisan('wizard:make', ['name' => 'TestWizard'])
            ->assertFailed();
    } finally {
        if (File::exists($backupPath)) {
            File::move($backupPath, $configPath);
        }
        File::cleanDirectory(app_path('Wizards'));
    }
});
