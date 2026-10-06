<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Http\Controllers\SkipStepController;
use Invelity\WizardPackage\Http\Controllers\StepController;
use Invelity\WizardPackage\Http\Controllers\WizardController;
use Invelity\WizardPackage\Tests\Fixtures\Steps\ConfirmationStep;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

it('registers no routes of its own', function () {
    expect(collect(Route::getRoutes()->getRoutes())->filter(
        fn (RoutingRoute $route): bool => isset($route->defaults['wizard'])
    ))->toBeEmpty();
});

it('registers the routes of a wizard like a resource', function () {
    $routes = Route::wizard('order', OrderWizard::class);
    Route::getRoutes()->refreshNameLookups();

    expect(array_map(fn (RoutingRoute $route): array => [
        implode('|', $route->methods()),
        $route->uri(),
        $route->getName(),
        $route->getActionName(),
    ], $routes))->toBe([
        ['GET|HEAD', 'order', 'order.show', WizardController::class.'@show'],
        ['POST', 'order', 'order.complete', WizardController::class.'@store'],
        ['DELETE', 'order', 'order.destroy', WizardController::class.'@destroy'],
        ['GET|HEAD', 'order/{step}', 'order.step', StepController::class.'@show'],
        ['POST', 'order/{step}', 'order.process', StepController::class.'@store'],
        ['POST', 'order/{step}/skip', 'order.skip', SkipStepController::class],
    ])
        ->and($routes[3]->wheres['step'])->toBe('calculator|personal\-data|newsletter|billing|summary|confirmation')
        ->and($routes[4]->wheres['step'])->toBe('calculator|personal\-data|newsletter|billing|summary')
        ->and($routes[5]->wheres['step'])->toBe('newsletter')
        ->and(array_unique(array_map(fn (RoutingRoute $route) => $route->defaults['wizard'], $routes)))->toBe([OrderWizard::class])
        ->and(route('order.step', 'summary'))->toBe('http://localhost/order/summary');
});

it('names nested routes after their static segments', function () {
    $routes = Route::wizard('/shops/{shop}/checkout/', OrderWizard::class);

    expect($routes[0]->uri())->toBe('shops/{shop}/checkout')
        ->and($routes[0]->getName())->toBe('shops.checkout.show')
        ->and($routes[3]->uri())->toBe('shops/{shop}/checkout/{step}');
});

it('applies the surrounding route group', function () {
    Route::prefix('api')->name('api.')->middleware(['api', 'auth'])->group(
        fn () => Route::wizard('order', OrderWizard::class)
    );
    Route::getRoutes()->refreshNameLookups();

    $route = Route::getRoutes()->getByName('api.order.process');

    expect($route?->uri())->toBe('api/order/{step}')
        ->and($route?->middleware())->toBe(['api', 'auth']);
});

it('leaves out the routes a wizard has no steps for', function () {
    $wizard = new class extends OrderWizard
    {
        protected string $name = 'review-only';

        protected array $steps = [ConfirmationStep::class];
    };

    app()->instance($wizard::class, $wizard);

    $names = array_map(fn (RoutingRoute $route): ?string => $route->getName(), Route::wizard('review', $wizard::class));

    expect($names)->toBe(['review.show', 'review.complete', 'review.destroy', 'review.step']);
});

it('uses the registered routes for step URLs', function () {
    Route::middleware('web')->group(fn () => Route::wizard('shops/{shop}/order', OrderWizard::class));

    Route::get('shops/{shop}/start', fn () => Wizard::for(OrderWizard::class)->url('summary'))->middleware('web');

    $this->get('/shops/acme/start')->assertSee('http://localhost/shops/acme/order/summary');
});
