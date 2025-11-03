<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use WebSystem\WizardPackage\Http\Controllers\WizardCompletionController;
use WebSystem\WizardPackage\Http\Controllers\WizardController;
use WebSystem\WizardPackage\Http\Controllers\WizardStepSkipController;

Route::group([
    'prefix' => config('wizard.route.prefix', 'wizard'),
    'as' => 'wizard.',
    'middleware' => config('wizard.route.middleware', ['web', 'wizard.session']),
], function () {
    Route::post('{wizard}/complete', WizardCompletionController::class)
        ->name('completed');

    Route::get('{wizard}/{wizardId}/edit/{step}', [WizardController::class, 'edit'])
        ->name('edit');

    Route::put('{wizard}/{wizardId}/edit/{step}', [WizardController::class, 'update'])
        ->name('update');

    Route::delete('{wizard}/{wizardId}', [WizardController::class, 'destroy'])
        ->name('destroy');

    Route::post('{wizard}/{step}/skip', WizardStepSkipController::class)
        ->name('skip');

    Route::get('{wizard}/{step}', [WizardController::class, 'show'])
        ->name('show');

    Route::post('{wizard}/{step}', [WizardController::class, 'store'])
        ->name('store');
});
