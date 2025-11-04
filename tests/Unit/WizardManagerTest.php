<?php

declare(strict_types=1);

test('getCurrentStep returns null when current step is null', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $storage->update('test', 'current_step', null);

    expect($manager->getCurrentStep())->toBeNull();
});

test('loadFromStorage loads existing session data', function () {
    config(['wizard.storage' => 'session', 'wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    $data = $storage->get('test');
    expect($data)->toHaveKey('wizard_id');

    $newManager = app(\Invelity\WizardPackage\Core\WizardManager::class);
    $newManager->loadFromStorage('test', 1);

    expect($newManager->getAllData())->toHaveKey('personal-info');
});

test('deleteWizard removes wizard from storage when not using database', function () {
    config(['wizard.storage' => 'session']);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class);
    expect($storage->exists('test'))->toBeTrue();

    $manager->deleteWizard('test', 1);

    expect($storage->exists('test'))->toBeFalse();
});

test('getNavigation throws exception when not initialized', function () {
    $manager = app(\Invelity\WizardPackage\Core\WizardManager::class);

    expect(fn () => $manager->getNavigation())
        ->toThrow(\RuntimeException::class);
});

test('navigateToStep throws exception when step not accessible', function () {
    config(['wizard.wizards.test.steps' => [
        \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
    ]]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');

    expect(fn () => $manager->navigateToStep('contact-details'))
        ->toThrow(\Invelity\WizardPackage\Exceptions\InvalidStepException::class);
});

test('loadFromStorage with database loads from WizardProgress model', function () {
    config([
        'wizard.storage' => 'database',
        'wizard.wizards.test.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        ],
    ]);

    $progress = \Invelity\WizardPackage\Models\WizardProgress::create([
        'wizard_id' => 'test',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now(),
    ]);

    $manager = app(\Invelity\WizardPackage\Core\WizardManager::class);
    $manager->loadFromStorage('test', $progress->id);

    expect($manager->getCurrentStep()->getId())->toBe('personal-info');
    expect($manager->getAllData())->toHaveKey('personal-info');
});

test('loadFromStorage with database throws exception when instance not found', function () {
    config(['wizard.storage' => 'database']);

    $manager = app(\Invelity\WizardPackage\Core\WizardManager::class);

    expect(fn () => $manager->loadFromStorage('test', 99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

test('deleteWizard with database removes WizardProgress record', function () {
    config(['wizard.storage' => 'database']);

    $progress = \Invelity\WizardPackage\Models\WizardProgress::create([
        'wizard_id' => 'test',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => [],
        'metadata' => [],
        'started_at' => now(),
    ]);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->deleteWizard('test', $progress->id);

    expect(\Invelity\WizardPackage\Models\WizardProgress::find($progress->id))->toBeNull();
});

test('deleteWizard with database throws exception when instance not found', function () {
    config(['wizard.storage' => 'database']);

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);

    expect(fn () => $manager->deleteWizard('test', 99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

test('getNavigation throws when navigation is null after initialization', function () {
    $manager = new \Invelity\WizardPackage\Core\WizardManager(
        app(\Invelity\WizardPackage\Core\WizardConfiguration::class),
        app(\Invelity\WizardPackage\Contracts\WizardStorageInterface::class),
        app(\Invelity\WizardPackage\Steps\StepFactory::class)
    );

    $reflection = new \ReflectionClass($manager);
    $property = $reflection->getProperty('currentWizardId');
    $property->setValue($manager, 'test');

    $navProperty = $reflection->getProperty('navigation');
    $navProperty->setValue($manager, null);

    expect(fn () => $manager->getNavigation())
        ->toThrow(\RuntimeException::class, 'Navigation not initialized.');
});
