<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\OptionalStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wizard.wizards.onboarding' => [
        'steps' => [
            PersonalInfoStep::class,
            OptionalStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('complete wizard flow from start to finish', function () {
    $manager = app(WizardManagerInterface::class);
    
    $manager->initialize('onboarding');
    
    $currentStep = $manager->getCurrentStep();
    expect($currentStep->getId())->toBe('personal-info');
    
    $result = $manager->processStep('personal-info', ['name' => 'John Doe']);
    expect($result->success)->toBeTrue();
    
    $progress = $manager->getProgress();
    expect($progress->completedSteps)->toBe(1)
        ->and($progress->totalSteps)->toBe(3);
    
    $nextStep = $manager->getNextStep();
    expect($nextStep)->not->toBeNull();
    
    $manager->processStep('optional-step', ['optional_field' => 'test']);
    
    $progress = $manager->getProgress();
    expect($progress->completedSteps)->toBe(2);
    
    $result = $manager->processStep('contact-details', ['email' => 'john@example.com']);
    expect($result->success)->toBeTrue();
    
    $progress = $manager->getProgress();
    expect($progress->isComplete)->toBeTrue();
    
    $result = $manager->complete();
    expect($result->success)->toBeTrue();
    
    $allData = $manager->getAllData();
    expect($allData)->toHaveKey('personal-info')
        ->and($allData)->toHaveKey('contact-details')
        ->and($allData['personal-info']['name'])->toBe('John Doe')
        ->and($allData['contact-details']['email'])->toBe('john@example.com');
});

test('wizard prevents skipping forward without completing previous steps', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    expect($manager->canAccessStep('personal-info'))->toBeTrue()
        ->and($manager->canAccessStep('contact-details'))->toBeFalse();
});

test('wizard allows navigation back to completed steps', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    expect($manager->canAccessStep('personal-info'))->toBeTrue();
    
    $manager->navigateToStep('personal-info');
    
    $currentStep = $manager->getCurrentStep();
    expect($currentStep->getId())->toBe('personal-info');
});

test('wizard validates step data before processing', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    expect(fn () => $manager->processStep('personal-info', ['name' => '']))
        ->toThrow(\Invelity\WizardPackage\Exceptions\StepValidationException::class);
});

test('wizard tracks progress accurately', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $progress = $manager->getProgress();
    expect($progress->percentComplete)->toBe(0);
    
    $manager->processStep('personal-info', ['name' => 'John']);
    $progress = $manager->getProgress();
    expect($progress->percentComplete)->toBeGreaterThan(0);
    
    $manager->skipStep('optional-step');
    $manager->processStep('contact-details', ['email' => 'john@example.com']);
    
    $progress = $manager->getProgress();
    expect($progress->percentComplete)->toBe(100);
});

test('wizard can be reset at any point', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('optional-step', ['optional_field' => 'value']);
    
    $manager->reset();
    
    $progress = $manager->getProgress();
    expect($progress->completedSteps)->toBe(0);
    
    $currentStep = $manager->getCurrentStep();
    expect($currentStep->getId())->toBe('personal-info');
});

test('wizard prevents completion when steps incomplete', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    $result = $manager->complete();
    expect($result->success)->toBeFalse()
        ->and($result->errors)->not->toBeEmpty();
});

test('wizard stores step data correctly', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'Jane Smith']);
    
    $allData = $manager->getAllData();
    expect($allData['personal-info']['name'])->toBe('Jane Smith');
    
    $manager->processStep('optional-step', ['optional_field' => 'test']);
    
    $allData = $manager->getAllData();
    expect($allData['personal-info']['name'])->toBe('Jane Smith')
        ->and($allData['optional-step']['optional_field'])->toBe('test');
});

test('wizard prevents skipping required steps', function () {
    $manager = app(WizardManagerInterface::class);
    
    config(['wizard.wizards.onboarding.steps' => [
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]]);
    
    $manager->initialize('onboarding');
    $manager->processStep('personal-info', ['name' => 'John']);
    
    expect(fn () => $manager->skipStep('contact-details'))
        ->toThrow(\Invelity\WizardPackage\Exceptions\InvalidStepException::class);
});

test('wizard returns next and previous steps correctly', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    expect($manager->getPreviousStep())->toBeNull();
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    $nextStep = $manager->getNextStep();
    expect($nextStep)->not->toBeNull();
    
    $manager->processStep('optional-step', ['optional_field' => 'test']);
    
    $manager->navigateToStep('optional-step');
    
    $previousStep = $manager->getPreviousStep();
    expect($previousStep)->not->toBeNull()
        ->and($previousStep->getId())->toBe('personal-info');
});
