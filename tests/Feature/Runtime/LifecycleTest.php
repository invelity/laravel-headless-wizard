<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Invelity\WizardPackage\Events\StepCompleted;
use Invelity\WizardPackage\Events\StepSkipped;
use Invelity\WizardPackage\Events\WizardCompleted;
use Invelity\WizardPackage\Events\WizardReset;
use Invelity\WizardPackage\Events\WizardStarted;
use Invelity\WizardPackage\Exceptions\WizardAlreadyCompletedException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

beforeEach(function () {
    Event::fake();

    $this->wizard = Wizard::for(OrderWizard::class);
});

/**
 * Finish every required step of the order wizard.
 */
function finishOrder(OrderWizard $wizard): void
{
    $wizard->process('calculator', ['weight' => '2']);
    $wizard->process('personal-data', ['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $wizard->skip('newsletter');
    $wizard->process('billing');
    $wizard->process('summary', ['terms' => 'yes']);
}

it('does not exist until something is stored', function () {
    expect($this->wizard->exists())->toBeFalse()
        ->and($this->wizard->state()->isStarted())->toBeFalse()
        ->and($this->wizard->current()?->id())->toBe('calculator')
        ->and(session()->has('wizard_order'))->toBeFalse();

    Event::assertNothingDispatched();
});

it('starts with metadata once', function () {
    $this->travelTo(CarbonImmutable::parse('2026-05-01 12:00:00'));

    $this->wizard->start(['carrier' => 'gls']);
    $this->wizard->start(['carrier' => 'dpd']);

    expect($this->wizard->exists())->toBeTrue()
        ->and($this->wizard->metadata())->toBe(['carrier' => 'gls'])
        ->and($this->wizard->state()->startedAt?->toDateTimeString())->toBe('2026-05-01 12:00:00')
        ->and($this->wizard->state()->current)->toBe('calculator');

    Event::assertDispatchedTimes(WizardStarted::class, 1);
    Event::assertDispatched(fn (WizardStarted $event): bool => $event->wizard === OrderWizard::class
        && $event->metadata === ['carrier' => 'gls']);
});

it('stores the processed data and moves to the next step', function () {
    $data = $this->wizard->process('calculator', ['weight' => '2,5']);

    expect($data)->toBe(['weight' => '2.5', 'price' => 11.25])
        ->and($this->wizard->data('calculator'))->toBe(['weight' => '2.5', 'price' => 11.25])
        ->and($this->wizard->current()?->id())->toBe('personal-data')
        ->and($this->wizard->state()->hasCompleted('calculator'))->toBeTrue()
        ->and(session('wizard_order.steps.calculator'))->toBe(['weight' => '2.5', 'price' => 11.25]);

    Event::assertDispatched(WizardStarted::class);
    Event::assertDispatched(fn (StepCompleted $event): bool => $event->step === 'calculator'
        && $event->data === ['weight' => '2.5', 'price' => 11.25]
        && $event->percentage === 20);
});

it('persists the state across instances', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    $fresh = Wizard::for(OrderWizard::class);

    expect($fresh->exists())->toBeTrue()
        ->and($fresh->data('calculator'))->toBe(['weight' => '2', 'price' => 9.0])
        ->and($fresh->current()?->id())->toBe('personal-data');
});

it('stores nothing for a step without a form request', function () {
    $this->wizard->process('calculator', ['weight' => '2']);
    $this->wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);

    expect($this->wizard->process('billing', ['unexpected' => 'input']))->toBe([])
        ->and($this->wizard->data('billing'))->toBe([]);
});

it('rejects invalid input without storing anything', function () {
    try {
        $this->wizard->process('calculator', ['weight' => 'heavy']);
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('weight');
    }

    expect($this->wizard->exists())->toBeFalse();

    Event::assertNotDispatched(StepCompleted::class);
});

it('skips optional steps', function () {
    $this->wizard->process('calculator', ['weight' => '2']);
    $this->wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);
    $this->wizard->skip('newsletter');

    expect($this->wizard->state()->hasSkipped('newsletter'))->toBeTrue()
        ->and($this->wizard->current()?->id())->toBe('billing');

    Event::assertDispatched(fn (StepSkipped $event): bool => $event->step === 'newsletter');
});

it('measures progress across the steps that take input', function () {
    finishOrder($this->wizard);

    expect($this->wizard->progress()->toArray())->toBe(['completed_steps' => 5, 'total_steps' => 5, 'percentage' => 100]);
});

it('completes once every required step is finished', function () {
    $this->travelTo(CarbonImmutable::parse('2026-05-01 12:00:00'));
    finishOrder($this->wizard);

    $data = $this->wizard->complete();

    expect($data)->toHaveKeys(['calculator', 'personal-data', 'billing', 'summary'])
        ->and($this->wizard->isCompleted())->toBeTrue()
        ->and($this->wizard->state()->completedAt?->toDateTimeString())->toBe('2026-05-01 12:00:00')
        ->and($this->wizard->current()?->id())->toBe('confirmation')
        ->and(session('wizard_order.status'))->toBe('completed');

    Event::assertDispatched(fn (WizardCompleted $event): bool => $event->data === $data);
});

it('refuses to complete while required steps are missing', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    try {
        $this->wizard->complete();
        $this->fail('The wizard completed.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['steps'][0])->toBe('Finish these steps first: Personal Data, Billing, Summary.');
    }

    Event::assertNotDispatched(WizardCompleted::class);
});

it('completes only once', function () {
    finishOrder($this->wizard);
    $this->wizard->complete();

    $this->wizard->complete();
})->throws(WizardAlreadyCompletedException::class, 'Wizard [order] has already been completed.');

it('refuses input after completion', function () {
    finishOrder($this->wizard);
    $this->wizard->complete();

    $this->wizard->process('calculator', ['weight' => '3']);
})->throws(WizardAlreadyCompletedException::class);

it('resets the stored state', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    $this->wizard->reset();

    expect($this->wizard->exists())->toBeFalse()
        ->and($this->wizard->data())->toBe([])
        ->and(session()->has('wizard_order'))->toBeFalse()
        ->and(Wizard::for(OrderWizard::class)->exists())->toBeFalse();

    Event::assertDispatched(WizardReset::class);
});

it('does not announce a reset of a wizard that never started', function () {
    $this->wizard->reset();

    Event::assertNotDispatched(WizardReset::class);
});
