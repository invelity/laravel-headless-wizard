<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Storage;

use WebSystem\WizardPackage\Contracts\WizardStorageInterface;
use WebSystem\WizardPackage\Models\WizardProgress;

class DatabaseStorage implements WizardStorageInterface
{
    public function put(string $key, array $data): void
    {
        WizardProgress::updateOrCreate(
            ['wizard_id' => $key],
            ['step_data' => $data]
        );
    }

    public function get(string $key): ?array
    {
        $progress = WizardProgress::where('wizard_id', $key)->first();

        return $progress?->step_data;
    }

    public function exists(string $key): bool
    {
        return WizardProgress::where('wizard_id', $key)->exists();
    }

    public function forget(string $key): void
    {
        WizardProgress::where('wizard_id', $key)->delete();
    }

    public function update(string $key, string $field, mixed $value): void
    {
        $data = $this->get($key) ?? [];
        data_set($data, $field, $value);
        $this->put($key, $data);
    }
}
