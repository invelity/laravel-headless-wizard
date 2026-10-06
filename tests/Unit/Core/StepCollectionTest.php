<?php

declare(strict_types=1);

use Invelity\WizardPackage\Exceptions\StepNotFoundException;
use Invelity\WizardPackage\StepCollection;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\PersonalDataStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\SummaryStep;

beforeEach(function () {
    $this->steps = new StepCollection([new CalculatorStep, new PersonalDataStep, new SummaryStep]);
});

it('finds steps by id', function () {
    expect($this->steps->find('personal-data'))->toBeInstanceOf(PersonalDataStep::class)
        ->and($this->steps->find('missing'))->toBeNull()
        ->and($this->steps->findOrFail('summary'))->toBeInstanceOf(SummaryStep::class);
});

it('throws for an unknown step', function () {
    $this->steps->findOrFail('missing');
})->throws(StepNotFoundException::class, 'Wizard step [missing] does not exist.');

it('carries the missing step on the exception', function () {
    try {
        $this->steps->findOrFail('missing');
    } catch (StepNotFoundException $exception) {
        expect($exception->step)->toBe('missing');
    }
});

it('finds steps by class', function () {
    expect($this->steps->findByClass(SummaryStep::class))->toBeInstanceOf(SummaryStep::class);
});

it('knows the ids and positions of its steps', function () {
    expect($this->steps->ids())->toBe(['calculator', 'personal-data', 'summary'])
        ->and($this->steps->position('calculator'))->toBe(0)
        ->and($this->steps->position('summary'))->toBe(2)
        ->and($this->steps->position('missing'))->toBeNull();
});

it('stays a step collection when filtered', function () {
    expect($this->steps->reject(fn ($step) => $step instanceof CalculatorStep))->toBeInstanceOf(StepCollection::class);
});
