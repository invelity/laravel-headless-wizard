<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Steps;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use WebSystem\WizardPackage\Contracts\WizardStepInterface;
use WebSystem\WizardPackage\Exceptions\InvalidStepException;

readonly class StepFactory
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  class-string<WizardStepInterface>  $stepClass
     *
     * @throws BindingResolutionException|InvalidStepException
     */
    public function make(string $stepClass): WizardStepInterface
    {
        if (! class_exists($stepClass)) {
            throw new InvalidStepException($stepClass);
        }

        $step = $this->container->make($stepClass);

        if (! $step instanceof WizardStepInterface) {
            throw new InvalidStepException($stepClass);
        }

        return $step;
    }

    /**
     * @param  array<class-string<WizardStepInterface>>  $stepClasses
     * @return array<WizardStepInterface>
     */
    public function makeMany(array $stepClasses): array
    {
        try {
            return array_map(
                fn (string $class) => $this->make($class),
                $stepClasses
            );
        } catch (BindingResolutionException|InvalidStepException $e) {
            return [];
        }
    }
}
