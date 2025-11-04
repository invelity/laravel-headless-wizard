<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface WizardStorageInterface
{
    public function put(string $key, array $data): void;

    public function get(string $key): ?array;

    public function exists(string $key): bool;

    public function forget(string $key): void;

    public function update(string $key, string $field, mixed $value): void;
}
