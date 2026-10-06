<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Invelity\WizardPackage\Exceptions\StepNotAccessibleException;
use Invelity\WizardPackage\Exceptions\StepNotFoundException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\ConfirmationStep;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

beforeEach(function () {
    $this->wizard = Wizard::for(OrderWizard::class);
});

it('refuses steps whose predecessors are not finished', function () {
    try {
        $this->wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);
        $this->fail('The step was processed.');
    } catch (StepNotAccessibleException $exception) {
        expect($exception->wizard)->toBe('order')
            ->and($exception->step)->toBe('personal-data')
            ->and($exception->current)->toBe('calculator');
    }

    expect($this->wizard->exists())->toBeFalse();
});

it('reports which steps are accessible', function () {
    expect($this->wizard->canAccess('calculator'))->toBeTrue()
        ->and($this->wizard->canAccess('personal-data'))->toBeFalse();

    $this->wizard->process('calculator', ['weight' => '2']);

    expect($this->wizard->canAccess('personal-data'))->toBeTrue();
});

it('refuses unknown steps', function () {
    $this->wizard->process('payment', []);
})->throws(StepNotFoundException::class, 'Wizard step [payment] does not exist.');

it('refuses to skip a required step', function () {
    try {
        $this->wizard->skip('calculator');
        $this->fail('The step was skipped.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['step' => ['The "Calculator" step cannot be skipped.']]);
    }
});

it('refuses input for display-only steps', function () {
    $wizard = new class extends OrderWizard
    {
        protected string $name = 'display-first';

        protected array $steps = [ConfirmationStep::class, CalculatorStep::class];
    };

    try {
        Wizard::for($wizard)->process('confirmation', ['anything' => true]);
        $this->fail('The step was processed.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['step' => ['The "Confirmation" step does not accept any input.']]);
    }
});

it('moves the visitor to an accessible step', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    $this->wizard->goTo('calculator');

    expect($this->wizard->current()?->id())->toBe('calculator')
        ->and(Wizard::for(OrderWizard::class)->current()?->id())->toBe('calculator');
});

it('does not move the visitor to an inaccessible step', function () {
    $this->wizard->goTo('summary');
})->throws(StepNotAccessibleException::class);

it('lets the visitor open any step of a wizard that allows jumping', function () {
    $wizard = Wizard::for(new class extends OrderWizard
    {
        protected string $name = 'jumping-order';

        protected bool $allowJumping = true;
    });

    expect($wizard->canAccess('billing'))->toBeTrue()
        ->and($wizard->canAccess('summary'))->toBeFalse();

    $wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);

    expect($wizard->current()?->id())->toBe('newsletter');
});

it('finds the steps around a step', function () {
    expect($this->wizard->next()?->id())->toBe('personal-data')
        ->and($this->wizard->next('summary')?->id())->toBe('confirmation')
        ->and($this->wizard->previous('personal-data')?->id())->toBe('calculator')
        ->and($this->wizard->previous())->toBeNull()
        ->and($this->wizard->firstUnfinished()?->id())->toBe('calculator');
});

it('builds the navigation from any step', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    $navigation = $this->wizard->navigation('calculator');

    expect($navigation->current)->toBe('calculator')
        ->and($navigation->next)->toBe('personal-data')
        ->and($navigation->canGoForward())->toBeTrue()
        ->and($this->wizard->navigation()->current)->toBe('personal-data');
});
