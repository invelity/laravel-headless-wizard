<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\Exceptions\StepAccessDeniedException;
use Invelity\WizardPackage\Exceptions\StepValidationException;
use Invelity\WizardPackage\Exceptions\WizardNotInitializedException;

test('invalid step exception has correct message', function () {
    $exception = new InvalidStepException('personal-info');

    expect($exception->getMessage())->toBe('Invalid wizard step: personal-info')
        ->and($exception)->toBeInstanceOf(Exception::class);
});

test('step access denied exception has correct message', function () {
    $exception = new StepAccessDeniedException('payment-info');

    expect($exception->getMessage())->toBe('Access denied to step: payment-info')
        ->and($exception)->toBeInstanceOf(Exception::class);
});

test('wizard not initialized exception has correct message', function () {
    $exception = new WizardNotInitializedException('user-wizard');

    expect($exception->getMessage())->toBe('Wizard not initialized: user-wizard')
        ->and($exception)->toBeInstanceOf(Exception::class);
});

test('step validation exception has errors', function () {
    $validator = Validator::make(
        ['email' => ''],
        ['email' => 'required']
    );

    throw new StepValidationException($validator);
})->throws(StepValidationException::class);

test('step validation exception can be thrown and caught', function () {
    $validator = Validator::make(
        ['name' => ''],
        ['name' => 'required']
    );

    try {
        throw new StepValidationException($validator);
    } catch (StepValidationException $e) {
        expect($e->getErrors())->toHaveKey('name');
    }
});
