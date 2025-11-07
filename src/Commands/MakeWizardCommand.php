<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class MakeWizardCommand extends Command
{
    protected $signature = 'wizard:make
                            {name? : The name of the wizard}
                            {--type= : Wizard type (blade, api, livewire, inertia)}
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
            error($validationError);

            return self::FAILURE;
        }

        $wizardClass = Str::studly($name);
        $wizardId = Str::kebab($name);
        $force = $this->option('force');

        $type = $this->option('type') ?? select(
            label: 'What type of wizard do you want to create?',
            options: [
                'blade' => 'Blade (Traditional server-side rendering)',
                'api' => 'API (Headless JSON responses)',
                'livewire' => 'Livewire (Reactive components)',
                'inertia' => 'Inertia.js (SPA with Vue/React)',
            ],
            default: 'blade',
            hint: 'Choose the frontend technology for your wizard'
        );

        if ($this->wizardExists($wizardClass) && ! $force) {
            error(__('Wizard \':class\' already exists. Use --force to overwrite.', ['class' => $wizardClass]));

            return self::FAILURE;
        }

        try {
            $this->createWizardClass($wizardClass, $wizardId);
            $this->createController($wizardClass, $wizardId, $type);

            if ($type === 'blade') {
                $this->createBladeViews($wizardClass, $wizardId);
            }

            info('Wizard created successfully!');
            note("Wizard class: app/Wizards/{$wizardClass}Wizard/{$wizardClass}.php");
            note("Controller: app/Http/Controllers/{$wizardClass}Controller.php");

            if ($type === 'blade') {
                note("Views: resources/views/wizards/{$wizardId}/");
            }

            if (in_array($type, ['api', 'livewire', 'inertia'])) {
                $this->newLine();
                warning('CSRF Protection Notice');
                note('For API/SPA wizards, add wizard routes to CSRF exceptions:');
                note('app/Http/Middleware/VerifyCsrfToken.php');
                note("protected \$except = ['api/wizards/{$wizardId}/*'];");
            }

            $this->newLine();
            note('Next steps:');
            note("  • Generate first step: php artisan wizard:make-step {$wizardClass}");
            note('  • Wizard will be auto-discovered on next request');

            return self::SUCCESS;
        } catch (\Exception $e) {
            error('Failed to create wizard: '.$e->getMessage());
            $this->newLine();
            note(__('Troubleshooting:'));
            note(__('  • Check directory permissions for app/Wizards/'));

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

    /**
     * @throws FileNotFoundException
     */
    protected function createController(string $wizardClass, string $wizardId, string $type): void
    {
        $stub = File::get(__DIR__.'/../../resources/stubs/controller.php.stub');
        $kebabName = Str::kebab($wizardClass);

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ wizardId }}', '{{ wizardKebab }}'],
            ['App\\Http\\Controllers', $wizardClass, $wizardId, $kebabName],
            $stub
        );

        $controllerDirectory = app_path('Http/Controllers');
        if (! File::isDirectory($controllerDirectory)) {
            File::makeDirectory($controllerDirectory, 0755, true);
        }

        $controllerPath = app_path("Http/Controllers/{$wizardClass}Controller.php");
        File::put($controllerPath, $content);
    }

    protected function createBladeViews(string $wizardClass, string $wizardId): void
    {
        $viewsDirectory = resource_path("views/wizards/{$wizardId}");
        File::makeDirectory($viewsDirectory, 0755, true);

        $stepsDirectory = "{$viewsDirectory}/steps";
        File::makeDirectory($stepsDirectory, 0755, true);
    }
}
