<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('has useWizard composable in resources', function () {
    $composablePath = __DIR__.'/../../../resources/js/composables/useWizard.js';

    expect($composablePath)->toBeFile();
});

it('has TypeScript definitions for useWizard', function () {
    $defsPath = __DIR__.'/../../../resources/js/composables/useWizard.d.ts';

    expect($defsPath)->toBeFile();
});

it('has useWizard in dist directory', function () {
    $distPath = __DIR__.'/../../../resources/dist/useWizard.js';

    expect($distPath)->toBeFile();
});

it('has TypeScript definitions in dist directory', function () {
    $distDefsPath = __DIR__.'/../../../resources/dist/useWizard.d.ts';

    expect($distDefsPath)->toBeFile();
});

it('useWizard exports the composable function', function () {
    $content = File::get(__DIR__.'/../../../resources/js/composables/useWizard.js');

    expect($content)
        ->toContain('export function useWizard')
        ->toContain('submitStep')
        ->toContain('goToStep')
        ->toContain('initialize');
});

it('useWizard has reactive state management', function () {
    $content = File::get(__DIR__.'/../../../resources/js/composables/useWizard.js');

    expect($content)
        ->toContain('reactive')
        ->toContain('computed')
        ->toContain('currentStepIndex')
        ->toContain('formData')
        ->toContain('errors');
});

it('useWizard handles API communication', function () {
    $content = File::get(__DIR__.'/../../../resources/js/composables/useWizard.js');

    expect($content)
        ->toContain('fetch')
        ->toContain('X-CSRF-TOKEN')
        ->toContain('application/json');
});

it('useWizard has form helpers', function () {
    $content = File::get(__DIR__.'/../../../resources/js/composables/useWizard.js');

    expect($content)
        ->toContain('setFieldValue')
        ->toContain('getFieldError')
        ->toContain('clearErrors');
});

it('assets are publishable', function () {
    $this->artisan('vendor:publish', ['--tag' => 'wizard-assets', '--force' => true])
        ->assertSuccessful();

    expect(true)->toBeTrue();
});
