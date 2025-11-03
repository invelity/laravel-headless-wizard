<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Traits;

trait HasWizardSteps
{
    protected array $wizardData = [];

    public function getWizardData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->wizardData;
        }

        return data_get($this->wizardData, $key);
    }

    public function setWizardData(array $data): void
    {
        $this->wizardData = $data;
    }

    public function mergeWizardData(array $data): void
    {
        $this->wizardData = array_merge($this->wizardData, $data);
    }
}
