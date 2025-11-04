<?php

declare(strict_types=1);

use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

beforeEach(function () {
    config(['wizard.wizards.test' => [
        'steps' => [
            PersonalInfoStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('facade can initialize wizard', function () {
    Wizard::initialize('test');

    expect(Wizard::getCurrentStep())->not->toBeNull();
    expect(Wizard::getCurrentStep()->getId())->toBe('personal-info');
});

test('facade can get current step', function () {
    Wizard::initialize('test');

    $step = Wizard::getCurrentStep();

    expect($step)->not->toBeNull();
    expect($step->getId())->toBe('personal-info');
});

test('facade can process step', function () {
    Wizard::initialize('test');

    $result = Wizard::processStep('personal-info', ['name' => 'John Doe']);

    expect($result->success)->toBeTrue();
});

test('facade can get progress', function () {
    Wizard::initialize('test');

    $progress = Wizard::getProgress();

    expect($progress)->toBeInstanceOf(\Invelity\WizardPackage\ValueObjects\WizardProgressValue::class);
    expect($progress->totalSteps)->toBe(2);
});

test('facade can get all data', function () {
    Wizard::initialize('test');
    Wizard::processStep('personal-info', ['name' => 'John']);

    $data = Wizard::getAllData();

    expect($data)->toBeArray();
    expect($data)->toHaveKey('personal-info');
});

test('facade can reset wizard', function () {
    Wizard::initialize('test');
    Wizard::processStep('personal-info', ['name' => 'John']);

    Wizard::reset();

    $step = Wizard::getCurrentStep();
    expect($step->getId())->toBe('personal-info');
    expect(Wizard::getAllData())->toBe([]);
});

test('facade resolves to correct service', function () {
    $instance = Wizard::getFacadeRoot();

    expect($instance)->toBeInstanceOf(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
});
