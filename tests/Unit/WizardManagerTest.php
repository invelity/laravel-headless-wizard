<?php

declare(strict_types=1);
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardManager;
use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\Models\WizardProgress;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('getCurrentStep returns null when current step is null', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(WizardStorageInterface::class);
    $storage->update('test', 'current_step', null);

    expect($manager->getCurrentStep())->toBeNull();
});

test('loadFromStorage loads existing session data', function () {
    config(['wizard.storage' => 'session', 'wizard.wizards.test.steps' => [
        PersonalInfoStep::class,
    ]]);

    $manager = app(WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);

    $storage = app(WizardStorageInterface::class);
    $data = $storage->get('test');
    expect($data)->toHaveKey('wizard_id');

    $newManager = app(WizardManager::class);
    $newManager->loadFromStorage('test', 1);

    expect($newManager->getAllData())->toHaveKey('personal-info');
});

test('deleteWizard removes wizard from storage when not using database', function () {
    config(['wizard.storage' => 'session']);

    $manager = app(WizardManagerInterface::class);
    $manager->initialize('test');

    $storage = app(WizardStorageInterface::class);
    expect($storage->exists('test'))->toBeTrue();

    $manager->deleteWizard('test', 1);

    expect($storage->exists('test'))->toBeFalse();
});

test('getNavigation throws exception when not initialized', function () {
    $manager = app(WizardManager::class);

    expect(fn () => $manager->getNavigation())
        ->toThrow(RuntimeException::class);
});

test('navigateToStep throws exception when step not accessible', function () {
    config(['wizard.wizards.test.steps' => [
        PersonalInfoStep::class,
        ContactDetailsStep::class,
    ]]);

    $manager = app(WizardManagerInterface::class);
    $manager->initialize('test');

    expect(fn () => $manager->navigateToStep('contact-details'))
        ->toThrow(InvalidStepException::class);
});

test('loadFromStorage with database loads from WizardProgress model', function () {
    config([
        'wizard.storage' => 'database',
        'wizard.wizards.test.steps' => [
            PersonalInfoStep::class,
        ],
    ]);

    $progress = WizardProgress::create([
        'wizard_id' => 'test',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now(),
    ]);

    $manager = app(WizardManager::class);
    $manager->loadFromStorage('test', $progress->id);

    expect($manager->getCurrentStep()->getId())->toBe('personal-info');
    expect($manager->getAllData())->toHaveKey('personal-info');
});

test('loadFromStorage with database throws exception when instance not found', function () {
    config(['wizard.storage' => 'database']);

    $manager = app(WizardManager::class);

    expect(fn () => $manager->loadFromStorage('test', 99999))
        ->toThrow(NotFoundHttpException::class);
});

test('deleteWizard with database removes WizardProgress record', function () {
    config(['wizard.storage' => 'database']);

    $progress = WizardProgress::create([
        'wizard_id' => 'test',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => [],
        'metadata' => [],
        'started_at' => now(),
    ]);

    $manager = app(WizardManagerInterface::class);
    $manager->deleteWizard('test', $progress->id);

    expect(WizardProgress::find($progress->id))->toBeNull();
});

test('deleteWizard with database throws exception when instance not found', function () {
    config(['wizard.storage' => 'database']);

    $manager = app(WizardManagerInterface::class);

    expect(fn () => $manager->deleteWizard('test', 99999))
        ->toThrow(NotFoundHttpException::class);
});
