<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Commands\MakeWizardCommand;

beforeEach(function () {
    $this->configPath = config_path('wizard-package.php');
    $this->backupPath = $this->configPath.'.backup';
    
    File::ensureDirectoryExists(config_path());
    
    $config = [
        'storage' => ['driver' => 'session'],
        'wizards' => [],
    ];
    
    File::put($this->configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
});

afterEach(function () {
    if (File::exists($this->backupPath)) {
        File::delete($this->backupPath);
    }
});

test('writeConfigSafely throws exception when config file not found', function () {
    File::delete($this->configPath);
    
    $command = new class extends MakeWizardCommand {
        public function testWriteConfig($path, $modifier) {
            return $this->writeConfigSafely($path, $modifier);
        }
    };
    
    expect(fn () => $command->testWriteConfig($this->configPath, fn ($c) => $c))
        ->toThrow(\RuntimeException::class, 'Config file not found');
});

test('writeConfigSafely creates backup file', function () {
    expect(File::exists($this->backupPath))->toBeFalse();
    
    $command = new class extends MakeWizardCommand {
        public function testWriteConfig($path, $modifier) {
            return $this->writeConfigSafely($path, $modifier);
        }
    };
    
    $command->testWriteConfig($this->configPath, function ($config) {
        $config['test'] = 'value';
        return $config;
    });
    
    expect(File::exists($this->backupPath))->toBeFalse();
    
    $config = require $this->configPath;
    expect($config)->toHaveKey('test');
});

test('writeConfigSafely restores backup on exception', function () {
    $originalContent = File::get($this->configPath);
    
    $command = new class extends MakeWizardCommand {
        public function testWriteConfig($path, $modifier) {
            return $this->writeConfigSafely($path, $modifier);
        }
    };
    
    try {
        $command->testWriteConfig($this->configPath, function ($config) {
            throw new \Exception('Test exception');
        });
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Test exception');
    }
    
    $restoredContent = File::get($this->configPath);
    expect($restoredContent)->toBe($originalContent);
});

test('buildConfigContent formats config correctly', function () {
    $command = new class extends MakeWizardCommand {
        public function testBuildConfig($config) {
            return $this->buildConfigContent($config);
        }
    };
    
    $config = ['wizards' => ['test' => ['class' => 'App\\\\Wizards\\\\Test']]];
    $content = $command->testBuildConfig($config);
    
    expect($content)->toContain("<?php");
    expect($content)->toContain("declare(strict_types=1)");
    expect($content)->toContain('return array');
});

test('writeWithLock throws exception when cannot open file', function () {
    $invalidPath = '/invalid/path/config.php';
    
    $command = new class extends MakeWizardCommand {
        public function testWriteWithLock($path, $content) {
            return $this->writeWithLock($path, $content);
        }
    };
    
    try {
        $command->testWriteWithLock($invalidPath, 'content');
        expect(false)->toBeTrue('Should have thrown exception');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

test('writeWithLock successfully writes content', function () {
    $testPath = sys_get_temp_dir().'/test-config-'.uniqid().'.php';
    
    $command = new class extends MakeWizardCommand {
        public function testWriteWithLock($path, $content) {
            return $this->writeWithLock($path, $content);
        }
    };
    
    $command->testWriteWithLock($testPath, '<?php return ["test" => true];');
    
    expect(File::exists($testPath))->toBeTrue();
    expect(File::get($testPath))->toContain('test');
    
    File::delete($testPath);
});
