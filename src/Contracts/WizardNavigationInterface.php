<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\NavigationItem;

interface WizardNavigationInterface
{
    /**
     * @return NavigationItem[]
     */
    public function getItems(): array;

    public function canNavigateTo(string $stepId): bool;

    public function canGoBack(): bool;

    public function canGoForward(): bool;

    public function getStepUrl(string $stepId, ?string $wizardId = null): ?string;
}
