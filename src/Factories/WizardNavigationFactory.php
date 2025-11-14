<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Factories;

use Invelity\WizardPackage\Contracts\StepFinderInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardNavigation;

class WizardNavigationFactory
{
    public function __construct(
        private readonly WizardStorageInterface $storage,
        private readonly WizardConfiguration $configuration,
        private readonly StepFinderInterface $stepFinder,
    ) {}

    public function create(array $steps, string $wizardId): WizardNavigationInterface
    {
        return new WizardNavigation(
            steps: $steps,
            storage: $this->storage,
            configuration: $this->configuration,
            wizardId: $wizardId,
            stepFinder: $this->stepFinder,
        );
    }
}
