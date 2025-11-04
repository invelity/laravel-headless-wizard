<?php

declare(strict_types=1);

use Invelity\WizardPackage\Actions\ProcessWizardStepAction;
use Invelity\WizardPackage\Actions\UpdateWizardStepAction;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

beforeEach(function () {
    config(['wizard.wizards.test' => [
        'steps' => [PersonalInfoStep::class],
    ]]);
});

test('ProcessWizardStepAction catches validation exception', function () {
    $action = app(ProcessWizardStepAction::class);

    try {
        $response = $action->execute('test', 'personal-info', []);
        expect($response->status())->toBe(422);
    } catch (\Invelity\WizardPackage\Exceptions\StepValidationException $e) {
        expect($e->getErrors())->toBeArray();
    }
});

test('UpdateWizardStepAction catches validation exception', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);
    $wizardId = session('test.wizard_id') ?? 1;

    $action = app(UpdateWizardStepAction::class);

    try {
        $response = $action->execute('test', $wizardId, 'personal-info', []);
        expect($response->status())->toBe(422);
    } catch (\Invelity\WizardPackage\Exceptions\StepValidationException $e) {
        expect($e->getErrors())->toBeArray();
    }
});
