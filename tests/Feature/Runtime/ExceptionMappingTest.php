<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::post('/order/{step}', fn (OrderWizard $wizard, string $step) => $wizard->process($step, request()));
        Route::post('/order/{step}/skip', function (OrderWizard $wizard, string $step) {
            $wizard->skip($step);

            return response()->noContent();
        });
        Route::post('/order', fn (OrderWizard $wizard) => $wizard->complete());
    });
});

it('answers unknown steps with 404', function () {
    $this->postJson('/order/payment')
        ->assertNotFound()
        ->assertJson(['message' => 'This step does not exist.']);
});

it('answers inaccessible steps with 403', function () {
    $this->postJson('/order/summary', ['terms' => 'yes'])
        ->assertForbidden()
        ->assertJson(['message' => 'Finish the previous steps before you continue.']);
});

it('answers invalid input with 422', function () {
    $this->postJson('/order/calculator', ['weight' => 'heavy'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('weight');
});

it('answers skipping a required step with 422', function () {
    $this->postJson('/order/calculator/skip')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['step' => 'The "Calculator" step cannot be skipped.']);
});

it('answers completing an unfinished wizard with 422', function () {
    $this->postJson('/order')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('steps');
});

it('answers changes to a completed wizard with 409', function () {
    $wizard = Wizard::for(OrderWizard::class);
    $wizard->process('calculator', ['weight' => '2']);
    $wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);
    $wizard->process('billing');
    $wizard->process('summary', ['terms' => 'yes']);
    $wizard->complete();

    $this->postJson('/order/calculator', ['weight' => '3'])
        ->assertConflict()
        ->assertJson(['message' => 'This wizard has already been completed.']);
});

it('translates the messages', function () {
    app()->setLocale('sk');

    $this->postJson('/order/summary')
        ->assertForbidden()
        ->assertJson(['message' => 'Pred pokračovaním dokončite predchádzajúce kroky.']);

    $this->postJson('/order/calculator/skip')
        ->assertJsonValidationErrors(['step' => 'Krok „Calculator“ nie je možné preskočiť.']);
});

it('redirects back with errors for HTML forms', function () {
    $this->from('/order/calculator')
        ->post('/order/calculator', ['weight' => 'heavy'])
        ->assertRedirect('/order/calculator')
        ->assertSessionHasErrors('weight');
});
