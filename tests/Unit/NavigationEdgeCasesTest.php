<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardNavigation;

test('canNavigateTo returns false when step not found', function () {
    $storage = app(WizardStorageInterface::class);
    $config = new WizardConfiguration(
        storage: 'session',
        navigation: ['allow_jump' => false],
        ui: [],
        validation: [],
        fireEvents: true
    );

    $steps = [
        new \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep,
        new \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep,
    ];

    $navigation = new WizardNavigation($steps, $storage, $config, 'test');

    $storage->put('test', [
        'current_step' => 'personal-info',
        'completed_steps' => [],
    ]);

    $result = $navigation->canNavigateTo('non-existent-step');

    expect($result)->toBeFalse();
});
