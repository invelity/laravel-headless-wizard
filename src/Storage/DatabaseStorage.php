<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Storage;

use Illuminate\Database\Eloquent\Builder;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Models\WizardProgress;

/**
 * Keeps one wizard progress row per wizard and visitor. The visitor's scope
 * key is stored in the session_id column, so the user id type does not matter.
 */
class DatabaseStorage implements WizardStorageInterface
{
    public function __construct(
        private readonly VisitorScope $scope = new VisitorScope,
    ) {}

    public function put(string $key, array $data): void
    {
        WizardProgress::updateOrCreate(
            ['wizard_id' => $key, 'session_id' => $this->scope->key()],
            [
                'step_data' => $data,
                'completed_steps' => [],
            ]
        );
    }

    public function get(string $key): ?array
    {
        $progress = $this->query($key)->first();

        return $progress?->step_data;
    }

    public function exists(string $key): bool
    {
        return $this->query($key)->exists();
    }

    public function forget(string $key): void
    {
        $this->query($key)->delete();
    }

    public function update(string $key, string $field, mixed $value): void
    {
        $data = $this->get($key) ?? [];
        data_set($data, $field, $value);
        $this->put($key, $data);
    }

    /**
     * @return Builder<WizardProgress>
     */
    private function query(string $key): Builder
    {
        return WizardProgress::query()
            ->where('wizard_id', $key)
            ->where('session_id', $this->scope->key());
    }
}
