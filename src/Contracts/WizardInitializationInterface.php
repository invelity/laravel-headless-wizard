<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface WizardInitializationInterface
{
    public function initialize(string $wizardId, array $config = []): void;

    public function loadFromStorage(string $wizardId, int $instanceId): void;

    public function reset(): void;
}
