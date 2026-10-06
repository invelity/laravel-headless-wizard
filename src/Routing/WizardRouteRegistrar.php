<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Http\Controllers\SkipStepController;
use Invelity\WizardPackage\Http\Controllers\StepController;
use Invelity\WizardPackage\Http\Controllers\WizardController;
use Invelity\WizardPackage\Wizard;

/**
 * Registers the JSON API of one wizard, the way Route::resource() registers a resource.
 */
final readonly class WizardRouteRegistrar
{
    /**
     * Create a new registrar instance.
     */
    public function __construct(
        private Router $router,
        private Factory $wizards,
    ) {}

    /**
     * Register the routes of a wizard under the given URI.
     *
     * | Method | URI                  | Name               | Action                         |
     * |--------|----------------------|--------------------|--------------------------------|
     * | GET    | {uri}                | {name}.show        | the wizard from its current step |
     * | POST   | {uri}                | {name}.complete    | complete the wizard            |
     * | DELETE | {uri}                | {name}.destroy     | reset the wizard               |
     * | GET    | {uri}/{step}         | {name}.step        | the wizard from a step         |
     * | POST   | {uri}/{step}         | {name}.process     | process a step                 |
     * | POST   | {uri}/{step}/skip    | {name}.skip        | skip an optional step          |
     *
     * The name is the URI with slashes turned into dots and parameters left out, as for resources.
     *
     * @param  class-string<Wizard>  $wizard
     * @return list<Route>
     */
    public function register(string $uri, string $wizard): array
    {
        $steps = $this->wizards->for($wizard)->steps();
        $uri = trim($uri, '/');
        $name = $this->name($uri);

        $routes = [
            $this->router->get($uri, [WizardController::class, 'show'])->name("{$name}.show"),
            $this->router->post($uri, [WizardController::class, 'store'])->name("{$name}.complete"),
            $this->router->delete($uri, [WizardController::class, 'destroy'])->name("{$name}.destroy"),
            $this->router->get("{$uri}/{step}", [StepController::class, 'show'])
                ->name("{$name}.step")
                ->where('step', $this->pattern($steps->ids())),
        ];

        $processable = $steps->reject(fn (Step $step): bool => $step->isDisplayOnly())->ids();

        if ($processable !== []) {
            $routes[] = $this->router->post("{$uri}/{step}", [StepController::class, 'store'])
                ->name("{$name}.process")
                ->where('step', $this->pattern($processable));
        }

        $optional = $steps->filter(fn (Step $step): bool => $step->isOptional())->ids();

        if ($optional !== []) {
            $routes[] = $this->router->post("{$uri}/{step}/skip", SkipStepController::class)
                ->name("{$name}.skip")
                ->where('step', $this->pattern($optional));
        }

        foreach ($routes as $route) {
            $route->defaults('wizard', $wizard);
        }

        return $routes;
    }

    /**
     * Get a route parameter pattern that matches exactly the given step identifiers.
     *
     * @param  list<string>  $ids
     */
    private function pattern(array $ids): string
    {
        return implode('|', array_map(fn (string $id): string => preg_quote($id), $ids));
    }

    /**
     * Get the route name for a URI.
     */
    private function name(string $uri): string
    {
        $segments = array_filter(
            explode('/', $uri),
            fn (string $segment): bool => $segment !== '' && ! Str::startsWith($segment, '{'),
        );

        return implode('.', $segments);
    }
}
