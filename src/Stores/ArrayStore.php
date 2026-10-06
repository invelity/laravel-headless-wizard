<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Stores;

use Invelity\WizardPackage\Contracts\Store;

/**
 * Keeps wizard states in memory for the lifetime of the store, which suits tests.
 */
final class ArrayStore implements Store
{
    /**
     * The stored states, keyed by wizard and scope.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $states = [];

    public function get(string $wizard, string $scope): ?array
    {
        return $this->states[$wizard][$scope] ?? null;
    }

    public function put(string $wizard, string $scope, array $state): void
    {
        $this->states[$wizard][$scope] = $state;
    }

    public function forget(string $wizard, string $scope): void
    {
        unset($this->states[$wizard][$scope]);
    }
}
