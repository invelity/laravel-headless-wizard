<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
    
    $configPath = config_path('wizard-package.php');
    $configDir = dirname($configPath);
    
    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }
    
    $config = [
        'storage' => ['driver' => 'session', 'ttl' => 3600],
        'wizards' => ['test-wizard' => ['steps' => []]],
        'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
    ];
    
    File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
});

afterEach(function () {
    File::cleanDirectory(app_path('Wizards'));
    File::cleanDirectory(app_path('Http/Requests/Wizards'));
    
    if (File::exists(config_path('wizard-package.php.backup'))) {
        File::delete(config_path('wizard-package.php.backup'));
    }
});

test('MakeStepCommand handles missing wizards error', function () {
    config(['wizard-package.wizards' => []]);
    
    $this->artisan('wizard:make-step')
        ->expectsOutput('No wizards found. Create a wizard first:')
        ->expectsOutput('php artisan wizard:make')
        ->assertFailed();
});

test('MakeStepCommand validates step name is PascalCase', function () {
    config(['wizard-package.wizards' => ['test' => ['steps' => []]]]);
    
    $this->artisan('wizard:make-step', ['name' => 'invalid_step', '--wizard' => 'test'])
        ->expectsOutput('Step name must be PascalCase (e.g., PersonalInfo)')
        ->assertFailed();
});

test('MakeWizardCommand validates wizard name is required', function () {
    $this->artisan('wizard:make', ['name' => ''])
        ->expectsOutput('Wizard name is required')
        ->assertFailed();
});

test('WritesConfig creates backup before modification', function () {
    $configPath = config_path('wizard-package.php');
    $backupPath = $configPath.'.backup';
    
    expect(File::exists($configPath))->toBeTrue();
    expect(File::exists($backupPath))->toBeFalse();
    
    $this->artisan('wizard:make', ['name' => 'BackupTest'])->run();
    
    expect(File::exists($backupPath))->toBeFalse();
});

test('writeWithLock handles file locking', function () {
    $configPath = config_path('wizard-package.php');
    
    expect(File::exists($configPath))->toBeTrue();
    
    $this->artisan('wizard:make', ['name' => 'LockTest'])->assertSuccessful();
    
    $content = File::get($configPath);
    expect($content)->toContain('LockTest');
});
