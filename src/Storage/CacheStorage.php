<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Storage;

use Illuminate\Contracts\Cache\Repository;
use WebSystem\WizardPackage\Contracts\WizardStorageInterface;

class CacheStorage implements WizardStorageInterface
{
    public function __construct(
        private readonly Repository $cache,
        private readonly int $ttl = 7200,
        private readonly string $prefix = 'wizard:',
    ) {}

    public function put(string $key, array $data): void
    {
        $this->cache->put($this->getKey($key), $data, $this->ttl);
    }

    public function get(string $key): ?array
    {
        return $this->cache->get($this->getKey($key));
    }

    public function exists(string $key): bool
    {
        return $this->cache->has($this->getKey($key));
    }

    public function forget(string $key): void
    {
        $this->cache->forget($this->getKey($key));
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
