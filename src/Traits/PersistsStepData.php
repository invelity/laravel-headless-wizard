<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Traits;

trait PersistsStepData
{
    protected array $stepData = [];

    public function loadStepData(array $data): void
    {
        $this->stepData = $data;
    }

    public function getStepData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->stepData;
        }

        return data_get($this->stepData, $key);
    }

    public function hasStepData(string $key): bool
    {
        return array_key_exists($key, $this->stepData);
    }
}
