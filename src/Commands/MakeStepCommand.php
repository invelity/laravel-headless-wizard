<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WebSystem\WizardPackage\Commands\Concerns\WritesConfig;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeStepCommand extends Command
{
    use WritesConfig;

    protected $signature = 'wizard:make-step
                            {name? : The name of the step}
                            {--wizard= : The wizard ID to add step to}
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

        if ($this->option('wizard')) {
            $wizardId = $this->option('wizard');
        } else {
            $wizardId = select(
                label: 'Which wizard should this step belong to?',
                options: array_combine(array_keys($wizards), array_map(fn ($id) => Str::title(str_replace('-', ' ', $id)), array_keys($wizards)))
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

        if ($this->stepExists($stepClass) && ! $force) {
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
            default: (string) ($this->getLastStepOrder($wizardId) + 1),
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
            $this->createStepClass($stepClass, $stepId, $title, (int) $order, $optional);
            $this->createFormRequestClass($stepClass);
            $this->registerInConfig($wizardId, $stepClass);
            $this->clearConfigCache();

            $requestClass = str_replace('Step', '', $stepClass).'Request';

            $this->info(__('✓ Step class created: app/Wizards/Steps/{class}.php', ['class' => $stepClass]));
            $this->info(__('✓ FormRequest created: app/Http/Requests/Wizards/{class}.php', ['class' => $requestClass]));
            $this->info(__('✓ Registered in wizard: {wizard}', ['wizard' => $wizardId]));
            $this->info(__('✓ Config cache cleared'));
            $this->newLine();
            $this->comment(__('Next steps:'));
            $this->comment(__('  • Add validation rules: app/Http/Requests/Wizards/{class}.php', ['class' => $requestClass]));
            $this->comment(__('  • Implement business logic: app/Wizards/Steps/{class}.php', ['class' => $stepClass]));
            $this->comment(__('  • Generate another step: php artisan wizard:make-step --wizard={wizard}', ['wizard' => $wizardId]));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error(__('Failed to create step: {message}', ['message' => $e->getMessage()]));
            $this->newLine();
            $this->comment(__('Troubleshooting:'));
            $this->comment(__('  • Check directory permissions for app/Wizards/Steps/'));
            $this->comment(__('  • Check directory permissions for app/Http/Requests/Wizards/'));
            $this->comment(__('  • Ensure config/wizard-package.php is writable'));

            return self::FAILURE;
        }
    }

    protected function getAvailableWizards(): array
    {
        return config('wizard-package.wizards', []);
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

    protected function stepExists(string $stepClass): bool
    {
        return File::exists(app_path("Wizards/Steps/{$stepClass}.php"));
    }

    protected function getLastStepOrder(string $wizardId): int
    {
        $config = config('wizard-package.wizards', []);
        $steps = $config[$wizardId]['steps'] ?? [];

        return count($steps);
    }

    protected function createStepClass(string $stepClass, string $stepId, string $title, int $order, bool $optional): void
    {
        $directory = app_path('Wizards/Steps');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/step.php.stub');

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ stepId }}', '{{ title }}', '{{ order }}', '{{ optional }}'],
            ['App\Wizards\Steps', $stepClass, $stepId, $title, (string) $order, $optional ? 'true' : 'false'],
            $stub
        );

        File::put(app_path("Wizards/Steps/{$stepClass}.php"), $content);
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

    /**
     * @throws Exception
     */
    protected function registerInConfig(string $wizardId, string $stepClass): void
    {
        $configPath = config_path('wizard-package.php');

        $this->writeConfigSafely($configPath, function (array $config) use ($wizardId, $stepClass) {
            $config['wizards'][$wizardId]['steps'][] = "App\\Wizards\\Steps\\{$stepClass}";

            return $config;
        });
    }

    protected function clearConfigCache(): void
    {
        Artisan::call('config:clear');
    }
}
