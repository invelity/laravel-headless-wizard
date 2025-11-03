<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Storage;

use Illuminate\Session\Store;
use WebSystem\WizardPackage\Contracts\WizardStorageInterface;

class SessionStorage implements WizardStorageInterface
{
    public function __construct(
        private readonly Store $session,
        private readonly string $prefix = 'wizard_',
    ) {}

    public function put(string $key, array $data): void
    {
        $this->session->put($this->getKey($key), $data);
    }

    public function get(string $key): ?array
    {
        return $this->session->get($this->getKey($key));
    }

    public function exists(string $key): bool
    {
        return $this->session->has($this->getKey($key));
    }

    public function forget(string $key): void
    {
        $this->session->forget($this->getKey($key));
    }

    public function update(string $key, string $field, mixed $value): void
    {
        $data = $this->get($key) ?? [];
        data_set($data, $field, $value);
        $this->put($key, $data);
    }

    private function getKey(string $key): string
    {
        return $this->prefix.$key;
    }
}
