<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;
use Invelity\WizardPackage\Wizard as BaseWizard;

afterEach(function () {
    Wizard::resolveUrlsUsing(null);
});

it('has no step URLs unless the application provides them', function () {
    $wizard = Wizard::for(OrderWizard::class);

    expect($wizard->url('calculator'))->toBeNull()
        ->and($wizard->navigation()->items[0]->url)->toBeNull();
});

it('resolves step URLs with a registered resolver', function () {
    Wizard::resolveUrlsUsing(fn (BaseWizard $wizard, Step $step): string => "/{$wizard->name()}/{$step->id()}");

    $wizard = Wizard::for(OrderWizard::class);

    expect($wizard->url('summary'))->toBe('/order/summary')
        ->and($wizard->navigation()->item('summary')?->url)->toBe('/order/summary');
});

it('lets a wizard link its steps to its own routes', function () {
    Wizard::resolveUrlsUsing(fn (): string => '/ignored');

    $wizard = Wizard::for(new class extends OrderWizard
    {
        protected string $name = 'linked-order';

        protected function stepUrl(Step $step): ?string
        {
            return $step->id() === 'calculator' ? '/order-wizard/calculator' : null;
        }
    });

    expect($wizard->url('calculator'))->toBe('/order-wizard/calculator')
        ->and($wizard->url('summary'))->toBe('/ignored');
});
