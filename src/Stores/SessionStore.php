<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Stores;

use Closure;
use Illuminate\Contracts\Session\Session;
use Invelity\WizardPackage\Contracts\Store;

/**
 * Keeps wizard states in the visitor's session.
 *
 * The session already belongs to one visitor, so the scope is not part of the key. The key
 * is "{prefix}{wizard}", the same as in 1.x, so sessions survive an upgrade.
 */
final readonly class SessionStore implements Store
{
    /**
     * Create a new session store.
     *
     * @param  Closure(): Session  $session  Resolves the session of the current request.
     */
    public function __construct(
        private Closure $session,
        private string $prefix = 'wizard_',
    ) {}

    public function get(string $wizard, string $scope): ?array
    {
        $state = $this->session()->get($this->key($wizard));

        /** @var array<string, mixed>|null */
        return is_array($state) ? $state : null;
    }

    public function put(string $wizard, string $scope, array $state): void
    {
        $this->session()->put($this->key($wizard), $state);
    }

    public function forget(string $wizard, string $scope): void
    {
        $this->session()->forget($this->key($wizard));
    }

    /**
     * Get the session of the current request.
     */
    private function session(): Session
    {
        return ($this->session)();
    }

    /**
     * Get the session key of a wizard.
     */
    private function key(string $wizard): string
    {
        return $this->prefix.$wizard;
    }
}
