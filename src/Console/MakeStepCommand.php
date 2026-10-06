<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\select;

#[AsCommand(name: 'wizard:make-step')]
final class MakeStepCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use ResolvesStubs;

    /**
     * The property that marks a step as optional.
     */
    private const string OPTIONAL = <<<'PHP'

        /**
         * Indicates if the visitor may skip the step.
         */
        protected bool $optional = true;

    PHP;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'wizard:make-step';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new wizard step and the form request that validates it';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Step';

    /**
     * Execute the console command.
     */
    public function handle(): ?bool
    {
        if (parent::handle() === false) {
            return false;
        }

        if (! $this->option('display')) {
            $this->createFormRequest();
        }

        $wizard = $this->option('wizard');

        if (is_string($wizard) && $wizard !== '') {
            $this->addToWizard($wizard);
        }

        if ($this->option('view') !== false) {
            $this->createView();
        }

        return null;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath($this->option('display') ? '/stubs/step.display.stub' : '/stubs/step.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Wizards\Steps';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        $request = $this->requestClass();

        return str_replace(
            ['{{ requestClass }}', '{{ request }}', '{{ optional }}'],
            [$request, class_basename($request), $this->option('optional') ? self::OPTIONAL : ''],
            parent::buildClass($name),
        );
    }

    /**
     * Get the console command options.
     *
     * @return list<array{0: string, 1: string|null, 2: int, 3: string, 4?: false}>
     */
    protected function getOptions(): array
    {
        return [
            ['wizard', 'w', InputOption::VALUE_REQUIRED, 'The wizard to add the step to'],
            ['optional', 'o', InputOption::VALUE_NONE, 'Let visitors skip the step'],
            ['display', 'd', InputOption::VALUE_NONE, 'Create a display-only step, which takes no input'],
            ['view', null, InputOption::VALUE_OPTIONAL, 'Create a Blade view for the step, named "{wizard}.{step}" unless a name is given', false],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the classes even if the step already exists'],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => ['What should the step be named?', 'E.g. PersonalDataStep'],
        ];
    }

    /**
     * Interact further with the user once the name has been prompted for.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $wizards = $this->existingWizards();

        if ($wizards !== []) {
            $wizard = select('Which wizard should the step be added to?', ['' => 'None', ...array_combine($wizards, $wizards)]);

            if (is_string($wizard) && $wizard !== '') {
                $input->setOption('wizard', $wizard);
            }
        }

        $kind = select('What kind of step is it?', [
            'input' => 'It takes input',
            'optional' => 'It takes input and may be skipped',
            'display' => 'It only displays information',
        ]);

        if ($kind === 'optional' || $kind === 'display') {
            $input->setOption($kind, true);
        }
    }

    /**
     * Create the form request that validates the step.
     */
    private function createFormRequest(): void
    {
        $class = $this->requestClass();
        $path = $this->getPath($class);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->warn(sprintf('Form request [%s] already exists.', $path));

            return;
        }

        $stub = $this->files->get($this->resolveStubPath('/stubs/step-request.stub'));

        $this->makeDirectory($path);
        $this->files->put($path, $this->replaceNamespace($stub, $class)->replaceClass($stub, $class));

        $this->components->info(sprintf('Form request [%s] created successfully.', $path));
    }

    /**
     * Create the Blade view of the step with Laravel's "make:view" command.
     *
     * Unless a name is given, "SummaryStep" of "OrderWizard" gets the view "order.summary": the default name of the
     * wizard, then the default id of the step.
     */
    private function createView(): void
    {
        $view = $this->option('view');
        $wizard = $this->option('wizard');

        if (! is_string($view) || $view === '') {
            if (! is_string($wizard) || $wizard === '') {
                $this->components->warn('Name the view (--view=order.summary) or the wizard (--wizard=OrderWizard) to create it.');

                return;
            }

            $view = Str::kebab($this->withoutSuffix($wizard, 'Wizard')).'.'.Str::kebab($this->withoutSuffix($this->getNameInput(), 'Step'));
        }

        $this->call('make:view', ['name' => $view, '--force' => $this->option('force')]);
    }

    /**
     * Get the base name of a class without the given suffix, as wizards and steps derive their default names.
     */
    private function withoutSuffix(string $class, string $suffix): string
    {
        $name = class_basename(str_replace('/', '\\', $class));

        return $name !== $suffix && Str::endsWith($name, $suffix) ? Str::beforeLast($name, $suffix) : $name;
    }

    /**
     * Add the step to the "$steps" array of the given wizard, importing its class.
     */
    private function addToWizard(string $wizard): void
    {
        $class = $this->qualifyWizard($wizard);
        $path = $this->getPath($class);
        $step = $this->qualifyClass($this->getNameInput());

        if (! $this->files->exists($path)) {
            $this->components->warn("Wizard [{$class}] does not exist. Add \\{$step}::class to its \$steps.");

            return;
        }

        $contents = $this->files->get($path);

        if ($this->references($contents, $step)) {
            $this->components->info("Step [{$step}] is already part of wizard [{$class}].");

            return;
        }

        [$imported, $reference] = $this->import($contents, $step);

        $updated = preg_replace_callback(
            '/(protected\s+array\s+\$steps\s*=\s*\[)(.*?)(\n([ \t]*)\];)/s',
            fn (array $matches): string => $matches[1]
                .rtrim((string) preg_replace('#\n[ \t]*//[ \t]*(?=\n|$)#', '', $matches[2]))
                ."\n".$matches[4].'    '.$reference.'::class,'
                .$matches[3],
            $imported,
            1,
            $count,
        );

        if ($count === 0 || $updated === null) {
            $this->components->warn("Could not find the \$steps array of [{$class}]. Add \\{$step}::class to it.");

            return;
        }

        $this->files->put($path, $updated);

        $this->components->info("Step [{$step}] added to wizard [{$class}].");
    }

    /**
     * Determine if the wizard source already lists the given step.
     */
    private function references(string $contents, string $step): bool
    {
        if (str_contains($contents, $step.'::class')) {
            return true;
        }

        return preg_match('/^use '.preg_quote($step, '/').';$/m', $contents) === 1
            && str_contains($contents, class_basename($step).'::class');
    }

    /**
     * Import a class into the source, keeping the imports sorted, and get how to refer to it.
     *
     * When the short name is already taken, the class is referred to by its full name instead.
     *
     * @return array{0: string, 1: string}
     */
    private function import(string $contents, string $class): array
    {
        $name = class_basename($class);

        if (preg_match('/^use [^;]*\\\\'.preg_quote($name, '/').';$/m', $contents) === 1
            || preg_match('/\bclass '.preg_quote($name, '/').'\b/', $contents) === 1) {
            return [$contents, '\\'.$class];
        }

        if (preg_match('/^(?:use (?!function |const )[^;]+;\n)+/m', $contents, $block, PREG_OFFSET_CAPTURE) === 1) {
            $lines = array_filter(explode("\n", $block[0][0]));
            $lines[] = "use {$class};";

            usort($lines, fn (string $a, string $b): int => strcasecmp(rtrim($a, ';'), rtrim($b, ';')));

            return [substr_replace($contents, implode("\n", $lines)."\n", $block[0][1], strlen($block[0][0])), $name];
        }

        $updated = preg_replace('/^(namespace [^;]+;\n)/m', "\$1\nuse {$class};\n", $contents, 1, $count);

        return $count === 1 && $updated !== null ? [$updated, $name] : [$contents, '\\'.$class];
    }

    /**
     * Get the class of the form request that validates the step.
     *
     * "Order/SummaryStep" is validated by "App\Http\Requests\Wizards\Order\SummaryRequest".
     */
    private function requestClass(): string
    {
        $step = $this->qualifyClass($this->getNameInput());
        $namespace = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\';
        $relative = Str::startsWith($step, $namespace) ? Str::after($step, $namespace) : class_basename($step);

        if (Str::endsWith($relative, 'Step')) {
            $relative = Str::beforeLast($relative, 'Step');
        }

        return $this->rootNamespace().'Http\Requests\Wizards\\'.$relative.'Request';
    }

    /**
     * Qualify the name of a wizard class.
     */
    private function qualifyWizard(string $wizard): string
    {
        $wizard = str_replace('/', '\\', ltrim($wizard, '\\'));

        return Str::startsWith($wizard, $this->rootNamespace()) ? $wizard : $this->rootNamespace().'Wizards\\'.$wizard;
    }

    /**
     * Get the names of the wizard classes in the application's "Wizards" directory.
     *
     * @return list<string>
     */
    private function existingWizards(): array
    {
        $wizards = [];

        foreach ($this->files->glob($this->laravel->path('Wizards/*.php')) as $file) {
            if (is_string($file)) {
                $wizards[] = basename($file, '.php');
            }
        }

        return $wizards;
    }
}
