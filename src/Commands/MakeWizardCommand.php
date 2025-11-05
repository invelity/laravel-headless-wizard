<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Commands;

use Illuminate\Console\Command;
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
            hint: 'Must be PascalCase. This will create app/Wizards/{Name}Wizard/{Name}.php'
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
            $this->error(__('Wizard \':class\' already exists. Use --force to overwrite.', ['class' => $wizardClass]));

            return self::FAILURE;
        }

        try {
            $this->createWizardClass($wizardClass, $wizardId);

            $this->info(__('✓ Wizard class created: app/Wizards/{wizard}Wizard/{class}.php', ['wizard' => $wizardClass, 'class' => $wizardClass]));
            $this->info(__('✓ Wizard directory created: app/Wizards/{wizard}Wizard/', ['wizard' => $wizardClass]));
            $this->newLine();
            $this->comment(__('Next steps:'));
            $this->comment(__('  • Generate first step: php artisan wizard:make-step {wizard}', ['wizard' => $wizardClass]));
            $this->comment(__('  • Wizard will be auto-discovered on next request'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create wizard: '.$e->getMessage());
            $this->newLine();
            $this->comment(__('Troubleshooting:'));
            $this->comment(__('  • Check directory permissions for app/Wizards/'));

            return self::FAILURE;
        }
    }

    protected function validateWizardName(string $value): ?string
    {
        if (empty($value)) {
            return __('Wizard name is required');
        }

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
            return __('Wizard name must be PascalCase (e.g., Onboarding)');
        }

        return null;
    }

    protected function wizardExists(string $wizardClass): bool
    {
        return File::exists(app_path("Wizards/{$wizardClass}Wizard/{$wizardClass}.php"));
    }

    protected function createWizardClass(string $wizardClass, string $wizardId): void
    {
        $directory = app_path("Wizards/{$wizardClass}Wizard");

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stepsDirectory = "{$directory}/Steps";
        if (! File::isDirectory($stepsDirectory)) {
            File::makeDirectory($stepsDirectory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/wizard.php.stub');

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ id }}', '{{ name }}'],
            ["App\\Wizards\\{$wizardClass}Wizard", $wizardClass, $wizardId, $wizardClass],
            $stub
        );

        File::put("{$directory}/{$wizardClass}.php", $content);
    }
}
