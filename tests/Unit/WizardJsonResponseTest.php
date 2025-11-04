<?php

declare(strict_types=1);

use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

test('stepAccessDenied returns 403 with error message', function () {
    $currentStep = new PersonalInfoStep();
    $response = WizardJsonResponse::stepAccessDenied($currentStep, 'contact-details');

    expect($response->status())->toBe(403);
    $data = $response->getData(true);
    expect($data['success'])->toBeFalse();
    expect($data['error'])->toBeString();
    expect($data['redirect_to'])->toBe('personal-info');
});

test('stepAccessDenied with null current step uses requested step', function () {
    $response = WizardJsonResponse::stepAccessDenied(null, 'contact-details');

    $data = $response->getData(true);
    expect($data['redirect_to'])->toBe('contact-details');
});

test('validationError returns 422 with errors array', function () {
    $errors = ['name' => ['Name is required'], 'email' => ['Email is invalid']];
    $response = WizardJsonResponse::validationError($errors);

    expect($response->status())->toBe(422);
    $data = $response->getData(true);
    expect($data['success'])->toBeFalse();
    expect($data['errors'])->toBe($errors);
});

test('stepProcessed returns success with next step', function () {
    $result = StepResult::success(['name' => 'John'], 'Step completed');
    $nextStep = new ContactDetailsStep();
    $progress = new WizardProgressValue(2, 1, 1, 50, ['contact-details'], false);

    $response = WizardJsonResponse::stepProcessed($result, $nextStep, $progress);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['next_step'])->toBe('contact-details');
    expect($data['data']['is_completed'])->toBeFalse();
    expect($data['data']['progress']['completion_percentage'])->toBe(50);
    expect($data['message'])->toBe('Step completed');
});

test('stepProcessed with null next step marks as completed', function () {
    $result = StepResult::success();
    $progress = new WizardProgressValue(2, 2, 2, 100, [], true);

    $response = WizardJsonResponse::stepProcessed($result, null, $progress);

    $data = $response->getData(true);
    expect($data['data']['next_step'])->toBeNull();
    expect($data['data']['is_completed'])->toBeTrue();
    expect($data['data']['progress']['is_complete'])->toBeTrue();
});

test('stepProcessed uses default message when result message is null', function () {
    $result = StepResult::success();
    $progress = new WizardProgressValue(2, 1, 1, 50, ['contact-details'], false);

    $response = WizardJsonResponse::stepProcessed($result, null, $progress);

    $data = $response->getData(true);
    expect($data['message'])->toBeString();
});

test('stepUpdated returns success with data', function () {
    $result = StepResult::success(['updated' => true], 'Data updated');
    $response = WizardJsonResponse::stepUpdated($result);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['data'])->toBe(['updated' => true]);
    expect($data['message'])->toBe('Data updated');
});

test('stepUpdated uses default message when result message is null', function () {
    $result = StepResult::success(['data' => 'value']);
    $response = WizardJsonResponse::stepUpdated($result);

    $data = $response->getData(true);
    expect($data['message'])->toBeString();
});

test('deleted returns success with message', function () {
    $response = WizardJsonResponse::deleted('Wizard removed');

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBe('Wizard removed');
});

test('deleted uses default message when null', function () {
    $response = WizardJsonResponse::deleted();

    $data = $response->getData(true);
    expect($data['message'])->toBeString();
});

test('completed returns success message', function () {
    $response = WizardJsonResponse::completed();

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBeString();
});

test('stepSkipped returns success with next step and progress', function () {
    $nextStep = new ContactDetailsStep();
    $progress = new WizardProgressValue(3, 2, 1, 66, ['contact-details'], false);

    $response = WizardJsonResponse::stepSkipped($nextStep, $progress);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['next_step'])->toBe('contact-details');
    expect($data['data']['is_completed'])->toBeFalse();
    expect($data['data']['progress']['completion_percentage'])->toBe(66);
    expect($data['message'])->toContain('skipped');
});

test('stepSkipped with null next step marks wizard as completed', function () {
    $progress = new WizardProgressValue(2, 2, 2, 100, [], true);

    $response = WizardJsonResponse::stepSkipped(null, $progress);

    $data = $response->getData(true);
    expect($data['data']['next_step'])->toBeNull();
    expect($data['data']['is_completed'])->toBeTrue();
});
