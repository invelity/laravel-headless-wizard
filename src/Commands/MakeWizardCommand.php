<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class MakeWizardCommand extends Command
{
    protected $signature = 'wizard:make 
                            {name? : The name of the wizard}
                            {--force : Overwrite existing wizard}';

    protected $description = 'Create a new wizard class';

    public function handle(): int
    {
        $name = $this->argument('name') ?? text(
            label: 'What is the wizard name?',
            placeholder: 'e.g., Onboarding, Registration',
            required: true,
            validate: fn (string $value) => $this->validateWizardName($value),
            hint: 'Must be PascalCase. This will create app/Wizards/{Name}.php'
        );

        $validationError = $this->validateWizardName($name);
        if ($validationError !== null) {
            $this->error($validationError);

            return self::FAILURE;
        }

        $wizardClass = Str::studly($name);
        $wizardId = Str::kebab($name);
        $force = $this->option('force');

        if ($this->wizardExists($wizardClass) && ! $force) {
            $this->error("Wizard '{$wizardClass}' already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        try {
            $this->createWizardClass($wizardClass, $wizardId);
            $this->registerInConfig($wizardId, $wizardClass);
            $this->clearConfigCache();

            $this->info("✓ Wizard class created: app/Wizards/{$wizardClass}.php");
            $this->info('✓ Registered in config: config/wizard-package.php');
            $this->info('✓ Config cache cleared');
            $this->newLine();
            $this->comment('Next steps:');
            $this->comment("  • Generate first step: php artisan wizard:make-step --wizard={$wizardId}");
            $this->comment('  • View wizard config: config/wizard-package.php');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create wizard: {$e->getMessage()}");
            $this->newLine();
            $this->comment('Troubleshooting:');
            $this->comment('  • Check directory permissions for app/Wizards/');
            $this->comment('  • Ensure config/wizard-package.php is writable');

            return self::FAILURE;
        }
    }

    protected function validateWizardName(string $value): ?string
    {
        if (empty($value)) {
            return 'Wizard name is required';
        }

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
            return 'Wizard name must be PascalCase (e.g., Onboarding)';
        }

        return null;
    }

    protected function wizardExists(string $wizardClass): bool
    {
        return File::exists(app_path("Wizards/{$wizardClass}.php"));
    }

    protected function createWizardClass(string $wizardClass, string $wizardId): void
    {
        $directory = app_path('Wizards');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/wizard.php.stub');

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ id }}', '{{ name }}'],
            ['App\\Wizards', $wizardClass, $wizardId, $wizardClass],
            $stub
        );

        File::put(app_path("Wizards/{$wizardClass}.php"), $content);
    }

    protected function registerInConfig(string $wizardId, string $wizardClass): void
    {
        $configPath = config_path('wizard-package.php');

        if (! File::exists($configPath)) {
            throw new \RuntimeException('Config file not found. Please publish the wizard config first.');
        }

        File::copy($configPath, $configPath.'.backup');

        try {
            $config = require $configPath;
            $config['wizards'][$wizardId] = [
                'class' => "App\\Wizards\\{$wizardClass}",
                'steps' => [],
            ];

            $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n";
            $content = str_replace("'App\\\\\\\\Wizards", "'App\\\\Wizards", $content);

            $handle = fopen($configPath, 'w');
            if (flock($handle, LOCK_EX)) {
                fwrite($handle, $content);
                flock($handle, LOCK_UN);
            }
            fclose($handle);

            File::delete($configPath.'.backup');
        } catch (\Exception $e) {
            if (File::exists($configPath.'.backup')) {
                File::move($configPath.'.backup', $configPath);
            }

            throw $e;
        }
    }

    protected function clearConfigCache(): void
    {
        Artisan::call('config:clear');
    }
}
