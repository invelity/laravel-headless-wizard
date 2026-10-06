<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;
use Invelity\WizardPackage\WizardManager;

beforeEach(function () {
    config(['wizard.default' => 'array']);
});

it('scopes the wizard to the authenticated user', function () {
    $this->actingAs((new User)->forceFill(['id' => 7]));

    expect(Wizard::for(OrderWizard::class)->scope())->toBe(User::class.'|7');
});

it('scopes guests to a token kept in their session', function () {
    $wizard = Wizard::for(OrderWizard::class);

    $scope = $wizard->scope();

    expect($scope)->toStartWith('session|')
        ->and(session(WizardManager::SCOPE_SESSION_KEY))->toBe(substr($scope, strlen('session|')))
        ->and(Wizard::for(OrderWizard::class)->scope())->toBe($scope);
});

it('keeps the guest token when the session is regenerated', function () {
    $session = app('session.store');
    $request = Request::create('/');
    $request->setLaravelSession($session);
    $this->app->instance('request', $request);

    $before = Wizard::for(OrderWizard::class)->scope();
    $session->regenerate();

    expect(Wizard::for(OrderWizard::class)->scope())->toBe($before);
});

it('accepts an explicit scope', function (mixed $scope, string $expected) {
    expect(Wizard::for(OrderWizard::class, value($scope))->scope())->toBe($expected);
})->with([
    'string' => ['tenant-1', 'tenant-1'],
    'integer' => [42, '42'],
    'stringable' => [str('order-9'), 'order-9'],
    'model' => [fn () => (new User)->forceFill(['id' => 3]), User::class.'|3'],
]);

it('refuses scopes it cannot turn into a key', function (mixed $scope) {
    Wizard::for(OrderWizard::class, value($scope));
})->with([
    'empty string' => [''],
    'array' => [['id' => 1]],
    'model without key' => [fn () => new User],
])->throws(InvalidArgumentException::class);

it('uses a custom scope resolver', function () {
    Wizard::resolveScopeUsing(fn (Request $request): string => 'tenant-'.$request->header('X-Tenant'));

    $request = Request::create('/');
    $request->headers->set('X-Tenant', 'acme');
    $this->app->instance('request', $request);

    expect(Wizard::for(OrderWizard::class)->scope())->toBe('tenant-acme');

    Wizard::resolveScopeUsing(null);
});

it('keeps the state of different scopes apart', function () {
    $jane = Wizard::for(OrderWizard::class, 'jane');
    $john = Wizard::for(OrderWizard::class, 'john');

    $jane->process('calculator', ['weight' => '2']);

    expect($john->exists())->toBeFalse()
        ->and(Wizard::for(OrderWizard::class, 'jane')->data('calculator'))->toBe(['weight' => '2', 'price' => 9.0])
        ->and(Wizard::for(OrderWizard::class, 'john')->data())->toBe([]);
});

it('hands out a fresh wizard on every call', function () {
    expect(Wizard::for(OrderWizard::class))->not->toBe(Wizard::for(OrderWizard::class));
});
