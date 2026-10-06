<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\MultipleInstanceManager;
use InvalidArgumentException;
use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\Stores\ArrayStore;
use Invelity\WizardPackage\Stores\CacheStore;
use Invelity\WizardPackage\Stores\DatabaseStore;
use Invelity\WizardPackage\Stores\SessionStore;

/**
 * Resolves the stores configured under "wizard.stores", like Laravel resolves cache stores.
 */
final class StoreManager extends MultipleInstanceManager
{
    /**
     * Get a store instance by name, or the default store.
     *
     * @throws InvalidArgumentException
     */
    public function store(?string $name = null): Store
    {
        $store = $this->instance($name);

        if (! $store instanceof Store) {
            throw new InvalidArgumentException(sprintf('Wizard store [%s] must implement [%s].', $name ?? $this->getDefaultInstance(), Store::class));
        }

        return $store;
    }

    public function getDefaultInstance(): string
    {
        $default = $this->config()->get('wizard.default');

        return is_string($default) ? $default : 'session';
    }

    /**
     * Set the default store name.
     *
     * @param  string  $name
     */
    public function setDefaultInstance($name): void
    {
        $this->config()->set('wizard.default', $name);
    }

    /**
     * Get the configuration of a store.
     *
     * @param  string  $name
     * @return array<string, mixed>|null
     */
    public function getInstanceConfig($name): ?array
    {
        $config = $this->config()->get("wizard.stores.{$name}");

        /** @var array<string, mixed>|null */
        return is_array($config) ? $config : null;
    }

    /**
     * Create a store that keeps the state in the visitor's session.
     *
     * The session is resolved on every call, so the store stays correct when one application
     * instance serves many requests, as under Laravel Octane.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createSessionDriver(array $config): SessionStore
    {
        return new SessionStore(fn (): Session => $this->currentSession(), $this->string($config, 'prefix', 'wizard_'));
    }

    /**
     * Create a store that keeps the state in a cache store.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createCacheDriver(array $config): CacheStore
    {
        $cache = $this->app->make(CacheFactory::class);
        $store = $config['store'] ?? null;
        $ttl = $config['ttl'] ?? null;

        return new CacheStore(
            $cache->store(is_string($store) ? $store : null),
            $this->string($config, 'prefix', 'wizard:'),
            is_numeric($ttl) ? (int) $ttl : null,
        );
    }

    /**
     * Create a store that keeps the state in a database table.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createDatabaseDriver(array $config): DatabaseStore
    {
        $connection = $config['connection'] ?? null;

        return new DatabaseStore(
            $this->app->make(ConnectionResolverInterface::class)->connection(is_string($connection) ? $connection : null),
            $this->string($config, 'table', 'wizard_states'),
            ($config['encrypt'] ?? true) === false ? null : $this->app->make(StringEncrypter::class),
        );
    }

    /**
     * Create a store that keeps the state in memory.
     */
    protected function createArrayDriver(): ArrayStore
    {
        return new ArrayStore;
    }

    /**
     * Get the session of the current request, or the application's session store.
     */
    private function currentSession(): Session
    {
        $request = $this->app->make('request');

        if ($request->hasSession()) {
            return $request->session();
        }

        return $this->app->make(Session::class);
    }

    /**
     * Get the configuration of the current application.
     *
     * The repository is resolved on every call, so a manager that Octane points at a new
     * application through setApplication() reads that application's configuration.
     */
    private function config(): Repository
    {
        return $this->app->make(Repository::class);
    }

    /**
     * Get a string option from a store configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function string(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
