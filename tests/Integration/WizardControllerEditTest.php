<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

test('it shows edit mode with prefilled data', function () {
    config(['wizard.wizards.checkout.steps' => [PersonalInfoStep::class]]);

    session()->put('checkout', [
        'wizard_id' => 'checkout',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'steps' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now()->toIso8601String(),
    ]);

    $response = $this->get(route('wizard.edit', [
        'wizard' => 'checkout',
        'wizardId' => 1,
        'step' => 'personal-info',
    ]));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'data' => [
            'is_edit_mode' => true,
        ],
    ]);
});

test('it validates data in edit mode', function () {
    config(['wizard.wizards.checkout.steps' => [PersonalInfoStep::class]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);
    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\UpdateWizardStepAction::class);

    try {
        $response = $action->execute('checkout', $wizardId, 'personal-info', []);
        expect($response->status())->toBe(422);
    } catch (\Invelity\WizardPackage\Exceptions\StepValidationException $e) {
        expect($e)->toBeInstanceOf(\Invelity\WizardPackage\Exceptions\StepValidationException::class);
    }
});

test('it returns access denied when step not accessible in edit mode', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\EditWizardStepAction::class);
    $response = $action->execute('checkout', $wizardId, 'contact-details');

    expect($response->status())->toBe(403);
});

test('it loads wizard from database in edit mode', function () {
    config(['wizard.wizards.checkout.steps' => [PersonalInfoStep::class]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);

    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\EditWizardStepAction::class);
    $response = $action->execute('checkout', $wizardId, 'personal-info');

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['is_edit_mode'])->toBeTrue();
});

test('it preserves changes when navigating between steps', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);

    $data = $manager->getAllData();
    expect($data)->toHaveKey('personal-info');
    expect($data)->toHaveKey('contact-details');
    expect($data['personal-info']['name'])->toBe('John');
    expect($data['contact-details']['email'])->toBe('john@example.com');
});

test('it updates single step without affecting others', function () {
    config(['wizard.wizards.checkout.steps' => [
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);

    $wizardId = session('checkout.wizard_id') ?? 1;

    $action = app(\Invelity\WizardPackage\Actions\UpdateWizardStepAction::class);
    $action->execute('checkout', $wizardId, 'personal-info', ['name' => 'Jane']);

    $data = $manager->getAllData();
    expect($data['personal-info']['name'])->toBe('Jane');
    expect($data['contact-details']['email'])->toBe('john@example.com');
});
