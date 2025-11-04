<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Storage;

use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;

readonly class CacheStorage implements WizardStorageInterface
{
    public function __construct(
        private Repository $cache,
        private int $ttl = 7200,
        private string $prefix = 'wizard:',
    ) {}

    public function put(string $key, array $data): void
    {
        $this->cache->put($this->getKey($key), $data, $this->ttl);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key): ?array
    {
        return $this->cache->get($this->getKey($key));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function exists(string $key): bool
    {
        return $this->cache->has($this->getKey($key));
    }

    public function forget(string $key): void
    {
        $this->cache->forget($this->getKey($key));
    }

    /**
     * @throws InvalidArgumentException
     */
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
