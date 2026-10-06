<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\Exceptions\InvalidWizardException;
use Invelity\WizardPackage\Validation\StepValidator;
use Stringable;

/**
 * Creates wizards bound to a visitor and the services they run on.
 *
 * The manager keeps no per-request state; every wizard instance it hands out carries its own.
 */
final class WizardManager implements Factory
{
    /**
     * The session key of the scope given to visitors who are not authenticated.
     */
    public const string SCOPE_SESSION_KEY = '_wizard_scope';

    /**
     * The callback that resolves the scope of the current visitor.
     *
     * @var (Closure(Request): mixed)|null
     */
    private ?Closure $scopeResolver = null;

    /**
     * The callback that resolves the URL of a step.
     *
     * @var (Closure(Wizard, Step): ?string)|null
     */
    private ?Closure $urlResolver = null;

    /**
     * Create a new wizard manager.
     */
    public function __construct(
        private Container $container,
        private readonly StoreManager $stores,
    ) {}

    public function for(string|Wizard $wizard, mixed $scope = null): Wizard
    {
        if (is_string($wizard) && ! is_subclass_of($wizard, Wizard::class)) {
            throw InvalidWizardException::notAWizard($wizard);
        }

        $instance = is_string($wizard) ? $this->resolve($wizard) : $wizard;

        if ($scope !== null || ! $instance->hasRuntime()) {
            $this->hydrate($instance, $scope === null ? null : $this->normalizeScope($scope));
        }

        return $instance;
    }

    public function store(?string $name = null): Store
    {
        return $this->stores->store($name);
    }

    public function extend(string $driver, Closure $callback): static
    {
        $this->stores->extend($driver, $callback);

        return $this;
    }

    public function resolveScopeUsing(?Closure $resolver): static
    {
        $this->scopeResolver = $resolver;

        return $this;
    }

    public function resolveUrlsUsing(?Closure $resolver): static
    {
        $this->urlResolver = $resolver;

        return $this;
    }

    /**
     * Bind a wizard to the services it runs on and to the given scope, or to the current visitor.
     *
     * @internal Called for every wizard the container resolves.
     */
    public function hydrate(Wizard $wizard, ?string $scope = null): void
    {
        $wizard->setRuntime(new Runtime(
            store: $this->stores->store($wizard->storeName()),
            container: $this->container,
            events: $this->container->make(Dispatcher::class),
            translator: $this->container->make(Translator::class),
            validator: new StepValidator($this->container),
            scopeResolver: $scope === null ? fn (): string => $this->resolveScope() : fn (): string => $scope,
            urlResolver: fn (Wizard $wizard, Step $step): ?string => $this->urlResolver === null ? null : ($this->urlResolver)($wizard, $step),
        ));
    }

    /**
     * Point the manager at the container that serves the current request.
     *
     * @internal Called for every request Laravel Octane receives.
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Turn a scope into the string stores key the state by.
     *
     * Models become "{morph class}|{key}", other authenticatable users "{class}|{identifier}".
     *
     * @throws InvalidArgumentException
     */
    public function normalizeScope(mixed $scope): string
    {
        $identifier = match (true) {
            $scope instanceof Model => [$scope->getMorphClass(), $scope->getKey()],
            $scope instanceof Authenticatable => [$scope::class, $scope->getAuthIdentifier()],
            default => null,
        };

        if ($identifier !== null) {
            return is_scalar($identifier[1]) && $identifier[1] !== ''
                ? $identifier[0].'|'.$identifier[1]
                : throw new InvalidArgumentException('A wizard scope cannot be a model or user without an identifier.');
        }

        return match (true) {
            is_string($scope) && $scope !== '', is_int($scope) => (string) $scope,
            $scope instanceof Stringable => (string) $scope,
            default => throw new InvalidArgumentException('A wizard scope must be a model, an authenticatable user, a string or an integer.'),
        };
    }

    /**
     * Resolve a wizard class from the container.
     *
     * @template TWizard of Wizard
     *
     * @param  class-string<TWizard>  $class
     * @return TWizard
     *
     * @throws InvalidWizardException
     */
    private function resolve(string $class): Wizard
    {
        $wizard = $this->container->make($class);

        if (! $wizard instanceof $class) {
            throw InvalidWizardException::notAWizard($class);
        }

        return $wizard;
    }

    /**
     * Resolve the scope of the current visitor.
     *
     * Without a custom resolver, that is the authenticated user, or else a random token kept
     * in the session, which survives the session being regenerated at login. Outside of a
     * request the application's session store keeps the token, so code that runs there and
     * uses a store other than the session should pass the scope to Wizard::for() explicitly.
     */
    private function resolveScope(): string
    {
        $request = $this->container->make('request');

        if ($this->scopeResolver !== null) {
            return $this->normalizeScope(($this->scopeResolver)($request));
        }

        if (($user = $this->container->make(AuthFactory::class)->guard()->user()) !== null) {
            return $this->normalizeScope($user);
        }

        $session = $request->hasSession() ? $request->session() : $this->container->make(Session::class);
        $token = $session->get(self::SCOPE_SESSION_KEY);

        if (! is_string($token)) {
            $session->put(self::SCOPE_SESSION_KEY, $token = Str::random(40));
        }

        return 'session|'.$token;
    }
}
