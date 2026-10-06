<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Stores;

use Illuminate\Contracts\Cache\Repository;
use Invelity\WizardPackage\Contracts\Store;

/**
 * Keeps wizard states in a cache store, one entry per wizard and scope.
 */
final readonly class CacheStore implements Store
{
    /**
     * Create a new cache store.
     *
     * @param  int|null  $ttl  The number of seconds a state is kept after its last change; null keeps it forever.
     */
    public function __construct(
        private Repository $cache,
        private string $prefix = 'wizard:',
        private ?int $ttl = null,
    ) {}

    public function get(string $wizard, string $scope): ?array
    {
        $state = $this->cache->get($this->key($wizard, $scope));

        /** @var array<string, mixed>|null */
        return is_array($state) ? $state : null;
    }

    public function put(string $wizard, string $scope, array $state): void
    {
        if ($this->ttl === null) {
            $this->cache->forever($this->key($wizard, $scope), $state);

            return;
        }

        $this->cache->put($this->key($wizard, $scope), $state, $this->ttl);
    }

    public function forget(string $wizard, string $scope): void
    {
        $this->cache->forget($this->key($wizard, $scope));
    }

    /**
     * Get the cache key of a wizard and scope.
     *
     * The scope is hashed, so any scope gives a key every cache driver accepts.
     */
    private function key(string $wizard, string $scope): string
    {
        return $this->prefix.$wizard.':'.hash('xxh128', $scope);
    }
}
