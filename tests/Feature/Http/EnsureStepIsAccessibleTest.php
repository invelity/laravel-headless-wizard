<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Http\Middleware\EnsureStepIsAccessible;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;
use Invelity\WizardPackage\Wizard as BaseWizard;

beforeEach(function () {
    Route::get('/checkout/{step}', fn (string $step) => "step:{$step}")
        ->middleware(['web', EnsureStepIsAccessible::using(OrderWizard::class)])
        ->name('checkout');
});

afterEach(function () {
    EnsureStepIsAccessible::redirectUsing(null);
});

it('lets visitors open accessible steps', function () {
    $this->get('/checkout/calculator')->assertOk()->assertSee('step:calculator');
});

it('redirects visitors to the step they should be on', function () {
    $this->get('/checkout/summary')->assertRedirect('http://localhost/checkout/calculator');

    Wizard::for(OrderWizard::class)->process('calculator', ['weight' => '2']);

    $this->get('/checkout/summary')->assertRedirect('http://localhost/checkout/personal-data');
});

it('answers JSON requests with 403', function () {
    $this->getJson('/checkout/summary')->assertForbidden();
});

it('answers unknown steps with 404', function () {
    $this->get('/checkout/payment')->assertNotFound();
});

it('redirects wherever the application wants', function () {
    EnsureStepIsAccessible::redirectUsing(
        fn (Request $request, BaseWizard $wizard, Step $step): string => "/start?step={$step->id()}"
    );

    $this->get('/checkout/summary')->assertRedirect('/start?step=calculator');
});

it('reads the step from a custom route parameter', function () {
    Route::get('/flow/{page}', fn (string $page) => "page:{$page}")
        ->middleware(['web', EnsureStepIsAccessible::using(OrderWizard::class, 'page')]);

    $this->get('/flow/summary')->assertRedirect('http://localhost/flow/calculator');
    $this->get('/flow/calculator')->assertOk();
});

it('is available under the wizard.step alias', function () {
    Route::get('/alias/{step}', fn (string $step) => $step)
        ->middleware(['web', 'wizard.step:'.OrderWizard::class]);

    $this->get('/alias/summary')->assertRedirect('http://localhost/alias/calculator');
});
