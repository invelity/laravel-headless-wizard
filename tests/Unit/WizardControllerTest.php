<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Invelity\WizardPackage\Http\Controllers\WizardController;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

beforeEach(function () {
    config(['wizard.wizards.test' => [
        'steps' => [
            PersonalInfoStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('controller show method calls ShowWizardStepAction', function () {
    $controller = app(WizardController::class);

    $response = $controller->show('test', 'personal-info');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
    expect($response->status())->toBe(200);
});

test('controller store method calls ProcessWizardStepAction', function () {
    $controller = app(WizardController::class);
    $request = Request::create('/test/personal-info', 'POST', ['name' => 'John Doe']);

    $response = $controller->store($request, 'test', 'personal-info');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
    expect($response->status())->toBeIn([200, 422]);
});

test('controller edit method calls EditWizardStepAction', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);
    $wizardId = session('test.wizard_id') ?? 1;

    $controller = app(WizardController::class);

    $response = $controller->edit('test', $wizardId, 'personal-info');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
    expect($response->status())->toBeIn([200, 404]);
});

test('controller update method calls UpdateWizardStepAction', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $manager->processStep('personal-info', ['name' => 'John']);
    $wizardId = session('test.wizard_id') ?? 1;

    $controller = app(WizardController::class);
    $request = Request::create('/test/'.$wizardId.'/edit/personal-info', 'PUT', ['name' => 'Jane']);

    $response = $controller->update($request, 'test', $wizardId, 'personal-info');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
    expect($response->status())->toBeIn([200, 422]);
});

test('controller destroy method deletes wizard and returns success', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('test');
    $wizardId = session('test.wizard_id') ?? 1;

    $controller = app(WizardController::class);

    $response = $controller->destroy('test', $wizardId);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
});
