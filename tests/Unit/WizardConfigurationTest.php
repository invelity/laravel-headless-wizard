<?php

declare(strict_types=1);

use Invelity\WizardPackage\Core\WizardConfiguration;

test('configuration can be created from config', function () {
    config(['wizard.storage' => 'session']);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config)->toBeInstanceOf(WizardConfiguration::class);
    expect($config->storage)->toBe('session');
});

test('configuration handles array storage config', function () {
    config(['wizard.storage' => ['driver' => 'database']]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->storage)->toBe('database');
});

test('configuration loads navigation settings', function () {
    config(['wizard.navigation' => ['allow_jump' => true]]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->navigation)->toHaveKey('allow_jump');
    expect($config->allowsJumpNavigation())->toBeTrue();
});

test('allowsJumpNavigation returns false by default', function () {
    config(['wizard.navigation' => []]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->allowsJumpNavigation())->toBeFalse();
});

test('showsAllSteps returns true by default', function () {
    config(['wizard.navigation' => []]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->showsAllSteps())->toBeTrue();
});

test('showsAllSteps returns configured value', function () {
    config(['wizard.navigation' => ['show_all_steps' => false]]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->showsAllSteps())->toBeFalse();
});

test('marksCompleted returns true by default', function () {
    config(['wizard.navigation' => []]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->marksCompleted())->toBeTrue();
});

test('marksCompleted returns configured value', function () {
    config(['wizard.navigation' => ['mark_completed' => false]]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->marksCompleted())->toBeFalse();
});

test('validateOnNavigate returns true by default', function () {
    config(['wizard.validation' => []]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->validateOnNavigate())->toBeTrue();
});

test('validateOnNavigate returns configured value', function () {
    config(['wizard.validation' => ['validate_on_navigate' => false]]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->validateOnNavigate())->toBeFalse();
});

test('allowsSkippingOptional returns true by default', function () {
    config(['wizard.validation' => []]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->allowsSkippingOptional())->toBeTrue();
});

test('allowsSkippingOptional returns configured value', function () {
    config(['wizard.validation' => ['allow_skip_optional' => false]]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->allowsSkippingOptional())->toBeFalse();
});

test('configuration loads fireEvents setting', function () {
    config(['wizard.events.fire_events' => false]);
    
    $config = WizardConfiguration::fromConfig();
    
    expect($config->fireEvents)->toBeFalse();
});
