<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Invelity\WizardPackage\Models\WizardProgress;

/**
 * Identifies the visitor whose wizard state the cache and database storages
 * read and write: the authenticated user, or else a random token kept in the
 * visitor's session, which survives the session id regeneration on login.
 */
final readonly class VisitorScope
{
    public const SESSION_KEY = '_wizard_scope';

    public function key(): string
    {
        $user = auth()->user();

        if ($user instanceof Authenticatable) {
            return 'user:'.$user::class.'|'.$user->getAuthIdentifier();
        }

        $token = session()->get(self::SESSION_KEY);

        if (! is_string($token) || $token === '') {
            $token = Str::random(40);

            session()->put(self::SESSION_KEY, $token);
        }

        return 'session:'.$token;
    }

    /**
     * Limit the query to the wizard progress rows the current visitor owns:
     * the rows the storage keeps for the visitor, and for authenticated users
     * the rows whose user_id is theirs.
     *
     * @param  Builder<WizardProgress>  $query
     * @return Builder<WizardProgress>
     */
    public function constrain(Builder $query): Builder
    {
        $key = $this->key();
        $userId = auth()->id();

        return $query->where(function (Builder $query) use ($key, $userId): void {
            $query->where('session_id', $key);

            if (is_int($userId)) {
                $query->orWhere('user_id', $userId);
            }
        });
    }
}
