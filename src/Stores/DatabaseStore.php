<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Stores;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Invelity\WizardPackage\Contracts\PrunableStore;

/**
 * Keeps wizard states in a database table, one row per wizard and scope.
 */
final readonly class DatabaseStore implements PrunableStore
{
    /**
     * Create a new database store.
     *
     * @param  StringEncrypter|null  $encrypter  Encrypts the stored state when given.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private string $table = 'wizard_states',
        private ?StringEncrypter $encrypter = null,
    ) {}

    public function get(string $wizard, string $scope): ?array
    {
        $state = $this->query()
            ->where('wizard', $wizard)
            ->where('scope', $scope)
            ->value('state');

        return is_string($state) ? $this->decode($state) : null;
    }

    public function put(string $wizard, string $scope, array $state): void
    {
        $now = CarbonImmutable::now();

        $this->query()->upsert(
            [[
                'wizard' => $wizard,
                'scope' => $scope,
                'state' => $this->encode($state),
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['wizard', 'scope'],
            ['state', 'updated_at'],
        );
    }

    public function forget(string $wizard, string $scope): void
    {
        $this->query()
            ->where('wizard', $wizard)
            ->where('scope', $scope)
            ->delete();
    }

    public function prune(DateTimeInterface $before): int
    {
        return $this->query()->where('updated_at', '<', $before)->delete();
    }

    /**
     * Get a query builder for the table.
     */
    private function query(): Builder
    {
        return $this->connection->table($this->table);
    }

    /**
     * Serialise a state for storage.
     *
     * @param  array<string, mixed>  $state
     */
    private function encode(array $state): string
    {
        $json = json_encode($state, JSON_THROW_ON_ERROR);

        return $this->encrypter?->encryptString($json) ?? $json;
    }

    /**
     * Restore a stored state.
     *
     * @return array<string, mixed>|null
     */
    private function decode(string $state): ?array
    {
        $json = $this->encrypter?->decryptString($state) ?? $state;

        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        /** @var array<string, mixed>|null */
        return is_array($decoded) ? $decoded : null;
    }
}
