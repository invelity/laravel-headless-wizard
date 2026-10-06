<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Exceptions\StepNotAccessibleException;
use Invelity\WizardPackage\Wizard;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps visitors on the steps they may open, for applications that serve the steps from their
 * own routes.
 *
 * Visitors who request a step they may not open yet are redirected to the step they should be
 * on: by default the same route with the step parameter replaced. JSON requests get a 403.
 */
final class EnsureStepIsAccessible
{
    /**
     * The callback that determines where to send visitors instead.
     *
     * @var (Closure(Request, Wizard, Step): (string|Response))|null
     */
    private static ?Closure $redirectCallback = null;

    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly Factory $wizards,
        private readonly UrlGenerator $url,
    ) {}

    /**
     * Get the middleware definition for the given wizard and route parameter.
     *
     * @param  class-string<Wizard>  $wizard
     */
    public static function using(string $wizard, string $parameter = 'step'): string
    {
        return self::class.':'.$wizard.','.$parameter;
    }

    /**
     * Specify where to send visitors who request a step they may not open yet.
     *
     * @param  (Closure(Request, Wizard, Step): (string|Response))|null  $callback
     */
    public static function redirectUsing(?Closure $callback): void
    {
        self::$redirectCallback = $callback;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     * @param  class-string<Wizard>  $wizard
     *
     * @throws StepNotAccessibleException
     */
    public function handle(Request $request, Closure $next, string $wizard, string $parameter = 'step'): Response
    {
        $instance = $this->wizards->for($wizard);
        $step = $request->route($parameter);

        if (! is_string($step) || $instance->canAccess($step)) {
            return $next($request);
        }

        $target = $instance->current();

        if ($target === null || $request->expectsJson()) {
            throw new StepNotAccessibleException($instance->name(), $step, $target?->id());
        }

        return $this->redirect($request, $instance, $target, $parameter);
    }

    /**
     * Send the visitor to the step they should be on.
     */
    private function redirect(Request $request, Wizard $wizard, Step $target, string $parameter): Response
    {
        if (self::$redirectCallback !== null) {
            $redirect = (self::$redirectCallback)($request, $wizard, $target);

            return is_string($redirect) ? new RedirectResponse($redirect) : $redirect;
        }

        $route = $request->route();

        if ($route === null) {
            return new RedirectResponse($wizard->url($target->id()) ?? '/');
        }

        return new RedirectResponse($this->url->toRoute($route, [...$route->parameters(), $parameter => $target->id()], true));
    }
}
