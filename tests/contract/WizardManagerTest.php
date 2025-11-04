<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

beforeEach(function () {
    config(['wizard.wizards.test-wizard.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);
    $this->manager = app(WizardManagerInterface::class);
});

test('it implements wizard manager interface', function () {
    expect($this->manager)
        ->toBeInstanceOf(WizardManagerInterface::class);
});

test('it can initialize wizard', function () {
    $this->manager->initialize('test-wizard', ['user_id' => 1]);

    expect($this->manager->getCurrentStep())
        ->toBeInstanceOf(WizardStepInterface::class);
});

test('it throws exception when getting step before initialization', function () {
    expect(fn () => $this->manager->getCurrentStep())
        ->toThrow(RuntimeException::class);
});

test('it can get step by id', function () {
    $this->manager->initialize('test-wizard');

    $step = $this->manager->getStep('personal-info');

    expect($step)
        ->toBeInstanceOf(WizardStepInterface::class)
        ->and($step->getId())->toBe('personal-info');
});

test('it throws exception for invalid step id', function () {
    $this->manager->initialize('test-wizard');

    expect(fn () => $this->manager->getStep('invalid-step'))
        ->toThrow(InvalidStepException::class);
});

test('it can process step with valid data', function () {
    $this->manager->initialize('test-wizard');

    $result = $this->manager->processStep('personal-info', [
        'name' => 'John Doe',
    ]);

    expect($result)
        ->toBeInstanceOf(StepResult::class)
        ->and($result->success)->toBeTrue();
});

test('it returns failure result for invalid data', function () {
    $this->manager->initialize('test-wizard');

    expect(fn () => $this->manager->processStep('personal-info', [
        'name' => '',
    ]))->toThrow(\Invelity\WizardPackage\Exceptions\StepValidationException::class);
});

test('it can navigate to next step', function () {
    $this->manager->initialize('test-wizard');

    $this->manager->processStep('personal-info', ['name' => 'John']);

    $nextStep = $this->manager->getNextStep();

    expect($nextStep)->toBeNull();
});

test('it can navigate to previous step', function () {
    $this->manager->initialize('test-wizard');
    $this->manager->processStep('personal-info', ['name' => 'John']);
    $this->manager->navigateToStep('contact-details');

    $previousStep = $this->manager->getPreviousStep();

    expect($previousStep)
        ->toBeInstanceOf(WizardStepInterface::class)
        ->and($previousStep->getId())->toBe('personal-info');
});

test('it can check step access', function () {
    $this->manager->initialize('test-wizard');

    expect($this->manager->canAccessStep('personal-info'))->toBeTrue()
        ->and($this->manager->canAccessStep('contact-details'))->toBeFalse();
});

test('it returns progress information', function () {
    $this->manager->initialize('test-wizard');

    $progress = $this->manager->getProgress();

    expect($progress)
        ->toBeInstanceOf(WizardProgressValue::class)
        ->and($progress->totalSteps)->toBeGreaterThan(0)
        ->and($progress->completedSteps)->toBe(0)
        ->and($progress->percentComplete)->toBe(0);
});

test('it can get all wizard data', function () {
    $this->manager->initialize('test-wizard');
    $this->manager->processStep('personal-info', ['name' => 'John']);

    $data = $this->manager->getAllData();

    expect($data)
        ->toBeArray()
        ->toHaveKey('personal-info')
        ->and($data['personal-info'])->toHaveKey('name', 'John');
});

test('it can complete wizard', function () {
    $this->manager->initialize('test-wizard');

    $this->manager->processStep('personal-info', ['name' => 'John']);
    $this->manager->processStep('contact-details', ['email' => 'john@example.com']);

    $result = $this->manager->complete();

    expect($result)
        ->toBeInstanceOf(StepResult::class)
        ->and($result->success)->toBeTrue();
});

test('it can reset wizard', function () {
    $this->manager->initialize('test-wizard');
    $this->manager->processStep('personal-info', ['name' => 'John']);

    $this->manager->reset();

    $progress = $this->manager->getProgress();
    expect($progress->completedSteps)->toBe(0);
});
