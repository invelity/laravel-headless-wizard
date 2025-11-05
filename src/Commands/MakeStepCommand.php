<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeStepCommand extends Command
{
    protected $signature = 'wizard:make-step
                            {wizard? : The wizard name}
                            {name? : The name of the step}
                            {--order= : Step order number}
                            {--optional= : Mark step as optional (true/false)}
                            {--force : Overwrite existing step}';

    protected $description = 'Create a new wizard step with FormRequest';

    public function handle(): int
    {
        $wizards = $this->getAvailableWizards();

        if (empty($wizards)) {
            $this->error(__('No wizards found. Create a wizard first:'));
            $this->comment('php artisan wizard:make');

            return self::FAILURE;
        }

        if ($this->argument('wizard')) {
            $wizardName = $this->argument('wizard');
        } else {
            $wizardName = select(
                label: 'Which wizard should this step belong to?',
                options: $wizards
            );
        }

        $name = $this->argument('name') ?? text(
            label: 'What is the step name?',
            placeholder: 'e.g., PersonalInfo, Preferences',
            required: true,
            validate: fn (string $value) => $this->validateStepName($value),
            hint: 'Must be PascalCase. This will create {Name}Step.php and {Name}Request.php'
        );

        $validationError = $this->validateStepName($name);
        if ($validationError !== null) {
            $this->error($validationError);

            return self::FAILURE;
        }

        $stepClass = Str::studly($name).'Step';
        $stepId = Str::kebab($name);
        $force = $this->option('force');

        if ($this->stepExists($stepClass, $wizardName) && ! $force) {
            $this->error(__('Step \':class\' already exists. Use --force to overwrite.', ['class' => $stepClass]));

            return self::FAILURE;
        }

        $title = text(
            label: 'What is the step title?',
            placeholder: 'e.g., Personal Information',
            required: true,
            hint: 'Human-readable title shown in navigation and UI'
        );

        $order = $this->option('order') ?? text(
            label: 'What is the step order?',
            placeholder: '1',
            default: (string) ($this->getLastStepOrder($wizardName) + 1),
            required: true,
            validate: fn (string $value) => ! is_numeric($value) ? 'Order must be a number' : null,
            hint: 'Numeric order for step sequence. Lower numbers appear first.'
        );

        $optionalOption = $this->option('optional');
        $optional = $optionalOption !== null ? (bool) $optionalOption : confirm(
            label: 'Is this step optional?',
            default: false,
            hint: 'Optional steps can be skipped by users'
        );

        try {
            $this->createStepClass($wizardName, $stepClass, $stepId, $title, (int) $order, $optional);
            $this->createFormRequestClass($stepClass);

            $requestClass = str_replace('Step', '', $stepClass).'Request';

            $this->info(__('✓ Step class created: app/Wizards/{wizard}Wizard/Steps/{class}.php', ['wizard' => $wizardName, 'class' => $stepClass]));
            $this->info(__('✓ FormRequest created: app/Http/Requests/Wizards/{class}.php', ['class' => $requestClass]));
            $this->info(__('✓ Step will be auto-discovered'));
            $this->newLine();
            $this->comment(__('Next steps:'));
            $this->comment(__('  • Add validation rules: app/Http/Requests/Wizards/{class}.php', ['class' => $requestClass]));
            $this->comment(__('  • Implement business logic: app/Wizards/{wizard}Wizard/Steps/{class}.php', ['wizard' => $wizardName, 'class' => $stepClass]));
            $this->comment(__('  • Generate another step: php artisan wizard:make-step {wizard}', ['wizard' => $wizardName]));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to create step: '.$e->getMessage());
            $this->newLine();
            $this->comment(__('Troubleshooting:'));
            $this->comment(__('  • Check directory permissions for app/Wizards/'));
            $this->comment(__('  • Check directory permissions for app/Http/Requests/Wizards/'));

            return self::FAILURE;
        }
    }

    protected function getAvailableWizards(): array
    {
        $wizardsPath = app_path('Wizards');
        
        if (!File::isDirectory($wizardsPath)) {
            return [];
        }
        
        $wizards = [];
        $directories = File::directories($wizardsPath);
        
        foreach ($directories as $dir) {
            $name = basename($dir);
            if (Str::endsWith($name, 'Wizard')) {
                $wizardName = Str::replaceLast('Wizard', '', $name);
                $wizards[$wizardName] = $wizardName;
            }
        }
        
        return $wizards;
    }

    protected function validateStepName(string $value): ?string
    {
        if (empty($value)) {
            return 'Step name is required';
        }

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
            return 'Step name must be PascalCase (e.g., PersonalInfo)';
        }

        return null;
    }

    protected function stepExists(string $stepClass, string $wizardName): bool
    {
        return File::exists(app_path("Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php"));
    }

    protected function getLastStepOrder(string $wizardName): int
    {
        $stepsPath = app_path("Wizards/{$wizardName}Wizard/Steps");
        
        if (!File::isDirectory($stepsPath)) {
            return 0;
        }
        
        $files = File::files($stepsPath);
        return count($files);
    }

    protected function createStepClass(string $wizardName, string $stepClass, string $stepId, string $title, int $order, bool $optional): void
    {
        $directory = app_path("Wizards/{$wizardName}Wizard/Steps");

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/step.php.stub');

        $requestClass = str_replace('Step', '', $stepClass).'Request';

        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ stepId }}',
                '{{ title }}',
                '{{ order }}',
                '{{ optional }}',
                '{{ formRequestNamespace }}',
                '{{ formRequestClass }}',
            ],
            [
                "App\\Wizards\\{$wizardName}Wizard\\Steps",
                $stepClass,
                $stepId,
                $title,
                (string) $order,
                $optional ? 'true' : 'false',
                'App\\Http\\Requests\\Wizards',
                $requestClass,
            ],
            $stub
        );

        File::put(app_path("Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php"), $content);
    }

    protected function createFormRequestClass(string $stepClass): void
    {
        $directory = app_path('Http/Requests/Wizards');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/request.php.stub');

        $requestClass = str_replace('Step', '', $stepClass).'Request';

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            ['App\Http\Requests\Wizards', $requestClass],
            $stub
        );

        File::put(app_path("Http/Requests/Wizards/{$requestClass}.php"), $content);
    }

}
