<?php

declare(strict_types=1);

test('canNavigateTo returns false when step not found', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();

    expect($navigation->canNavigateTo('non-existent-step'))->toBeFalse();
});

test('canNavigateTo returns false when dependencies not met', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();

    expect($navigation->canNavigateTo('contact-details'))->toBeFalse();
});

test('canGoBack returns false when current step is null', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', null);

    $navigation = $manager->getNavigation();

    expect($navigation->canGoBack())->toBeFalse();
});

test('canGoForward returns false when current step is null', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', null);

    $navigation = $manager->getNavigation();

    expect($navigation->canGoForward())->toBeFalse();
});

test('getNextStep returns null when current step index not found', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', 'invalid-step');

    $navigation = $manager->getNavigation();

    expect($navigation->getNextStep('invalid-step'))->toBeNull();
});

test('getPreviousStep returns null when current step index not found or is zero', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();

    expect($navigation->getPreviousStep('invalid-step'))->toBeNull();
    expect($navigation->getPreviousStep('personal-info'))->toBeNull();
});

test('getPreviousStep returns null when current step is null', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', null);

    $navigation = $manager->getNavigation();

    expect($navigation->getPreviousStep())->toBeNull();
});

test('canNavigateTo returns true when jump navigation is enabled', function () {
    config([
        'wizard.navigation.allow_jump' => true,
        'wizard.wizards.test.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ],
    ]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();

    expect($navigation->canNavigateTo('contact-details'))->toBeTrue();
});

test('canNavigateTo returns false when has missing dependency', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();

    expect($navigation->canNavigateTo('contact-details'))->toBeFalse();
});

test('getNextStep returns first step when current step is null', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $wizardData = $storage->get('test');
    $wizardData['current_step'] = null;
    $storage->put('test', $wizardData);

    $navigation = $manager->getNavigation();
    $nextStep = $navigation->getNextStep();

    expect($nextStep)->not->toBeNull();
    expect($nextStep->getId())->toBe('personal-info');
});

test('getNextStep continues when step should not be skipped', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $navigation = $manager->getNavigation();
    $nextStep = $navigation->getNextStep('personal-info');

    expect($nextStep->getId())->toBe('contact-details');
});

test('getPreviousStep returns previous non-skipped step', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'test@example.com']);

    $navigation = $manager->getNavigation();
    $prevStep = $navigation->getPreviousStep('contact-details');

    expect($prevStep->getId())->toBe('personal-info');
});

test('getStepUrl generates correct route', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $navigation = $manager->getNavigation();
    $url = $navigation->getStepUrl('personal-info');

    expect($url)->toContain('wizard');
    expect($url)->toContain('personal-info');
});

test('canNavigateTo returns false when step has unmet dependencies', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\DependentStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $navigation = $manager->getNavigation();

    expect($navigation->canNavigateTo('dependent-step'))->toBeFalse();
});

test('getNextStep skips steps when shouldSkip returns true', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ConditionalStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'steps.skip_conditional', true);

    $navigation = $manager->getNavigation();
    $nextStep = $navigation->getNextStep('personal-info');

    expect($nextStep->getId())->toBe('contact-details');
});

test('getPreviousStep skips steps when shouldSkip returns true', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ConditionalStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'test@example.com']);

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'steps.skip_conditional', true);

    $navigation = $manager->getNavigation();
    $prevStep = $navigation->getPreviousStep('contact-details');

    expect($prevStep->getId())->toBe('personal-info');
});

test('getPreviousStep returns null when all previous steps should be skipped', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\ConditionalStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', 'contact-details');
    $storage->update('test', 'steps.skip_conditional', true);

    $navigation = $manager->getNavigation();
    $prevStep = $navigation->getPreviousStep('contact-details');

    expect($prevStep)->toBeNull();
});

test('getStepsBefore returns empty array when step index not found', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $navigation = $manager->getNavigation();

    $result = $navigation->canNavigateTo('invalid-step-with-required-previous');

    expect($result)->toBeFalse();
});
