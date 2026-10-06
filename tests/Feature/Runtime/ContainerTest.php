<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Exceptions\InvalidWizardException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\StoreManager;
use Invelity\WizardPackage\Stores\ArrayStore;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;
use Invelity\WizardPackage\WizardManager;

it('resolves the manager as the factory behind the facade', function () {
    expect(app(Factory::class))->toBeInstanceOf(WizardManager::class)
        ->and(app(Factory::class))->toBe(app(WizardManager::class))
        ->and(Wizard::getFacadeRoot())->toBe(app(WizardManager::class));
});

it('binds wizards resolved from the container to the current visitor', function () {
    $wizard = app(OrderWizard::class);

    $wizard->process('calculator', ['weight' => '2']);

    expect(Wizard::for(OrderWizard::class)->data('calculator'))->toBe(['weight' => '2', 'price' => 9.0]);
});

it('injects wizards into controllers', function () {
    Route::post('/order/{step}', fn (OrderWizard $wizard, string $step) => $wizard->process($step, request()))
        ->middleware('web');

    $this->post('/order/calculator', ['weight' => '3'])
        ->assertOk()
        ->assertExactJson(['weight' => '3', 'price' => 13.5]);
});

it('binds wizards that were created directly', function () {
    $wizard = Wizard::for(new OrderWizard);

    expect($wizard->current()?->id())->toBe('calculator');
});

it('rejects wizards used before they were bound', function () {
    (new OrderWizard)->current();
})->throws(InvalidWizardException::class, 'was created directly; resolve it from the container or with Wizard::for().');

it('rejects classes that are not wizards', function () {
    Wizard::for(stdClass::class);
})->throws(InvalidWizardException::class, '[stdClass] is not a wizard');

it('uses the store the wizard names', function () {
    $store = new ArrayStore;
    app(StoreManager::class)->extend('memory', fn () => $store);
    config(['wizard.stores.memory' => ['driver' => 'memory']]);

    $wizard = Wizard::for(new class extends OrderWizard
    {
        protected string $name = 'stored-in-memory';

        protected ?string $store = 'memory';
    }, 'visitor');

    $wizard->process('calculator', ['weight' => '2']);

    expect($store->get('stored-in-memory', 'visitor'))->toHaveKey('steps.calculator')
        ->and(session()->has('wizard_stored-in-memory'))->toBeFalse();
});

it('exposes the stores and accepts custom drivers through the facade', function () {
    $store = new ArrayStore;
    config(['wizard.stores.custom' => ['driver' => 'custom']]);

    Wizard::extend('custom', fn () => $store);

    expect(Wizard::store('custom'))->toBe($store);
});
