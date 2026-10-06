---
layout: default
title: Configuration
nav_order: 3
---

# Configuration

Publish the configuration file with `php artisan vendor:publish --tag=wizard-config`.

```php
return [
    'default' => env('WIZARD_STORE', 'session'),

    'stores' => [
        'session' => ['driver' => 'session', 'prefix' => 'wizard_'],
        'cache' => ['driver' => 'cache', 'store' => env('WIZARD_CACHE_STORE'), 'prefix' => 'wizard:', 'ttl' => 60 * 60 * 24],
        'database' => ['driver' => 'database', 'connection' => env('WIZARD_DB_CONNECTION'), 'table' => 'wizard_states', 'encrypt' => true],
        'array' => ['driver' => 'array'],
    ],
];
```

## Stores

| Driver | Options | Notes |
| --- | --- | --- |
| `session` | `prefix` | Keys the state as `{prefix}{wizard name}`. The session already belongs to one visitor, so the scope is not part of the key. |
| `cache` | `store`, `prefix`, `ttl` | `store` is a cache store from `config/cache.php` (null for the default). `ttl` is in seconds; `null` keeps the state forever. |
| `database` | `connection`, `table`, `encrypt` | One row per wizard and scope. The state is encrypted with the application key unless `encrypt` is `false`. Supports `wizard:prune`. |
| `array` | — | Keeps the state in memory for the lifetime of the process. Meant for tests. |

A wizard can use another store than the default:

```php
class OrderWizard extends Wizard
{
    protected ?string $store = 'database';
}
```

### Custom stores

A store implements `Invelity\WizardPackage\Contracts\Store`, and `PrunableStore` if it can remove old states:

```php
use Invelity\WizardPackage\Contracts\Store;

final class RedisJsonStore implements Store
{
    public function get(string $wizard, string $scope): ?array { /* ... */ }
    public function put(string $wizard, string $scope, array $state): void { /* ... */ }
    public function forget(string $wizard, string $scope): void { /* ... */ }
}
```

Register the driver in a service provider and reference it from the configuration:

```php
Wizard::extend('redis-json', fn (Application $app, array $config) => new RedisJsonStore($config['connection']));
```

```php
'stores' => [
    'redis' => ['driver' => 'redis-json', 'connection' => 'default'],
],
```

## Scope

The scope tells the cache and database stores whose wizard they hold. By default it is:

1. the authenticated user (`App\Models\User|42`), or else
2. a random token kept in the session (`session|…`), which survives `session()->regenerate()` at login.

Resolve it differently, for example per team in an area only signed-in users reach:

```php
Wizard::resolveScopeUsing(fn (Request $request) => $request->user()->currentTeam);
```

The resolver must return a model, an authenticatable user, a non-empty string, an integer or a `Stringable` value.
Models become `{morph class}|{key}`; the other values are used as they are.

Load another visitor's wizard, for example in a job or an admin screen, by passing the scope explicitly:

```php
$wizard = Wizard::for(OrderWizard::class, $user);
```

Explicit scopes need the cache or database store. The session store only ever sees the current visitor's session.

## Step URLs

Navigation items carry a URL when the application can provide one. The wizard looks for it in this order:

1. the wizard's own `stepUrl()` method;
2. `Wizard::resolveUrlsUsing()`;
3. the routes registered with `Route::wizard()`;
4. otherwise `null`.

```php
class OrderWizard extends Wizard
{
    protected function stepUrl(Step $step): ?string
    {
        return route('order.step', $step->id());
    }
}
```

## Translations

The messages the package shows to visitors live in `wizard::messages` and ship in English and Slovak. Publish them with
`--tag=wizard-translations` to change them or add a language.

## Laravel Octane

The wizard manager keeps no state between requests, and the provider points it at every request's sandbox. Resolve
wizards per request (inject them, or call `Wizard::for()`), and never store a wizard instance in a singleton.
