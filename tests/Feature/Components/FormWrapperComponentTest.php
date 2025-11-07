<?php

declare(strict_types=1);

use Invelity\WizardPackage\Components\FormWrapper;

it('creates form wrapper with action', function () {
    $component = new FormWrapper(
        action: '/wizard/test/step1',
        method: 'POST'
    );

    expect($component->action)->toBe('/wizard/test/step1');
    expect($component->method)->toBe('POST');
});

it('uses POST as default method', function () {
    $component = new FormWrapper(
        action: '/wizard/test/step2'
    );

    expect($component->method)->toBe('POST');
});

it('has correct view path', function () {
    $component = new FormWrapper(action: '/test');
    $view = $component->render();

    expect($view->name())->toBe('wizard-package::components.form-wrapper');
});

it('accepts different HTTP methods', function () {
    $component = new FormWrapper(
        action: '/test',
        method: 'PUT'
    );

    expect($component->method)->toBe('PUT');
});
