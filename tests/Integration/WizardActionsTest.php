<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wizard.wizards.checkout' => [
        'steps' => [
            PersonalInfoStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('UpdateWizardStepAction processes step successfully', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\UpdateWizardStepAction::class);
    $response = $action->execute('checkout', $wizardId, 'personal-info', ['name' => 'John Doe']);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBeString();
});

test('UpdateWizardStepAction handles step processing', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);
    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\UpdateWizardStepAction::class);
    $response = $action->execute('checkout', $wizardId, 'personal-info', ['name' => 'Jane']);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
});

test('SkipWizardStepAction skips step successfully', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
        ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);

    $action = app(\Invelity\WizardPackage\Actions\SkipWizardStepAction::class);
    $response = $action->execute('checkout', 'optional-step');

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toContain('skipped');
});

test('SkipWizardStepAction returns progress information', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');

    $action = app(\Invelity\WizardPackage\Actions\SkipWizardStepAction::class);
    $response = $action->execute('checkout', 'optional-step');

    $data = $response->getData(true);
    expect($data['data']['progress'])->toHaveKeys(['completion_percentage', 'is_complete']);
    expect($data['data']['progress']['completion_percentage'])->toBeInt();
});

test('SkipWizardStepAction completes wizard when no more steps', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);

    $action = app(\Invelity\WizardPackage\Actions\SkipWizardStepAction::class);
    $response = $action->execute('checkout', 'optional-step');

    $data = $response->getData(true);
    expect($data['data']['next_step'])->toBeNull();
    expect($data['data']['is_completed'])->toBeTrue();
});

test('UpdateWizardStepAction loads wizard from storage', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'Initial Name']);

    $wizardId = session('checkout.wizard_id') ?? 1;
    session()->forget('checkout');

    $action = app(\Invelity\WizardPackage\Actions\UpdateWizardStepAction::class);
    $response = $action->execute('checkout', $wizardId, 'personal-info', ['name' => 'Updated Name']);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
});
