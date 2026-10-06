<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Translation\Translator;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\Validation\StepValidator;

/**
 * The services one wizard instance runs on, provided by the wizard manager.
 *
 * @internal
 */
final class Runtime
{
    /**
     * The resolved scope.
     */
    private ?string $scope = null;

    /**
     * Create a new runtime instance.
     *
     * @param  Closure(): string  $scopeResolver  Resolves the scope when it is first needed.
     * @param  Closure(Wizard, Step): ?string  $urlResolver
     */
    public function __construct(
        public readonly Store $store,
        public readonly Container $container,
        public readonly Dispatcher $events,
        public readonly Translator $translator,
        public readonly StepValidator $validator,
        private readonly Closure $scopeResolver,
        private readonly Closure $urlResolver,
    ) {}

    /**
     * Get the scope of the visitor.
     */
    public function scope(): string
    {
        return $this->scope ??= ($this->scopeResolver)();
    }

    /**
     * Get the URL of a step of a wizard.
     */
    public function url(Wizard $wizard, Step $step): ?string
    {
        return ($this->urlResolver)($wizard, $step);
    }

    /**
     * Translate a message of the package.
     *
     * @param  array<string, string>  $replace
     */
    public function trans(string $key, array $replace = []): string
    {
        $message = $this->translator->get("wizard::messages.{$key}", $replace);

        return is_string($message) ? $message : $key;
    }
}
