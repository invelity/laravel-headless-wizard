<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Invelity\WizardPackage\Generators\FormRequestGenerator;
use Invelity\WizardPackage\Generators\StepGenerator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeStepCommand extends Command
{
    public function __construct(
        private readonly StepGenerator $stepGenerator,
        private readonly FormRequestGenerator $formRequestGenerator,
    ) {
        parent::__construct();
    }
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
            error(__('No wizards found. Create a wizard first:'));
            note('php artisan wizard:make');

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
            error($validationError);

            return self::FAILURE;
        }

        $stepClass = Str::studly($name).'Step';
        $stepId = Str::kebab($name);
        $force = $this->option('force');

        if ($this->stepGenerator->exists($stepClass, $wizardName) && ! $force) {
            error(__('Step \':class\' already exists. Use --force to overwrite.', ['class' => $stepClass]));

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
            default: (string) ($this->stepGenerator->getLastStepOrder($wizardName) + 1),
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
            $this->stepGenerator->reorderExistingSteps($wizardName, (int) $order);
            $this->stepGenerator->generate($wizardName, $stepClass, $stepId, $title, (int) $order, $optional);
            $this->formRequestGenerator->generate($stepClass);

            $requestClass = str_replace('Step', '', $stepClass).'Request';

            info('Step created successfully!');
            note("Step class: app/Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php");
            note("FormRequest: app/Http/Requests/Wizards/{$requestClass}.php");
            note('Step will be auto-discovered');
            $this->newLine();
            note('Next steps:');
            note("  • Add validation rules: app/Http/Requests/Wizards/{$requestClass}.php");
            note("  • Implement business logic: app/Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php");
            note("  • Generate another step: php artisan wizard:make-step {$wizardName}");

            return self::SUCCESS;
        } catch (Exception $e) {
            error('Failed to create step: '.$e->getMessage());
            $this->newLine();
            note(__('Troubleshooting:'));
            note(__('  • Check directory permissions for app/Wizards/'));
            note(__('  • Check directory permissions for app/Http/Requests/Wizards/'));

            return self::FAILURE;
        }
    }

    protected function getAvailableWizards(): array
    {
        $wizardsPath = app_path('Wizards');

        if (! File::isDirectory($wizardsPath)) {
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

}
