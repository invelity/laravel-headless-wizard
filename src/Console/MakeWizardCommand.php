<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'wizard:make')]
final class MakeWizardCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use ResolvesStubs;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'wizard:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new wizard class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Wizard';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/wizard.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Wizards';
    }

    /**
     * Get the console command options.
     *
     * @return list<array{0: string, 1: string|null, 2: int, 3: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the wizard already exists'],
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
            'name' => ['What should the wizard be named?', 'E.g. OrderWizard'],
        ];
    }
}
