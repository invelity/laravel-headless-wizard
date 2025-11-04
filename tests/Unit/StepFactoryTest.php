<?php

declare(strict_types=1);

use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\Steps\StepFactory;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;

test('factory creates valid step instance', function () {
    $factory = app(StepFactory::class);
    
    $step = $factory->make(PersonalInfoStep::class);
    
    expect($step)->toBeInstanceOf(\Invelity\WizardPackage\Contracts\WizardStepInterface::class);
    expect($step->getId())->toBe('personal-info');
});

test('factory throws exception for non-existent class', function () {
    $factory = app(StepFactory::class);
    
    $factory->make('NonExistentClass');
})->throws(InvalidStepException::class);

test('factory throws exception for invalid step class', function () {
    $factory = app(StepFactory::class);
    
    $factory->make(\stdClass::class);
})->throws(InvalidStepException::class);

test('factory creates multiple steps at once', function () {
    $factory = app(StepFactory::class);
    
    $steps = $factory->makeMany([
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]);
    
    expect($steps)->toHaveCount(2);
    expect($steps[0])->toBeInstanceOf(\Invelity\WizardPackage\Contracts\WizardStepInterface::class);
    expect($steps[1])->toBeInstanceOf(\Invelity\WizardPackage\Contracts\WizardStepInterface::class);
});

test('factory returns empty array when makeMany fails', function () {
    $factory = app(StepFactory::class);
    
    $steps = $factory->makeMany([
        'NonExistentClass',
        PersonalInfoStep::class,
    ]);
    
    expect($steps)->toBe([]);
});

test('factory returns empty array for invalid step in makeMany', function () {
    $factory = app(StepFactory::class);
    
    $steps = $factory->makeMany([
        \stdClass::class,
    ]);
    
    expect($steps)->toBe([]);
});
