<?php

declare(strict_types=1);

use Invelity\WizardPackage\Components\Layout;

it('renders layout component with title', function () {
    $component = new Layout(title: 'Test Wizard');

    expect($component->title)->toBe('Test Wizard');
    expect($component->render())->toBeObject();
});

it('renders layout component with default title', function () {
    $component = new Layout;

    expect($component->title)->toBe('Wizard');
});

it('has correct view path', function () {
    $component = new Layout;
    $view = $component->render();

    expect($view->name())->toBe('wizard-package::components.layout');
});

it('layout component is publishable', function () {
    $this->artisan('vendor:publish', ['--tag' => 'wizard-package-views', '--force' => true])
        ->assertSuccessful();

    expect(true)->toBeTrue();
});
