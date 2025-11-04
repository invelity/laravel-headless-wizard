<?php

declare(strict_types=1);

use Invelity\WizardPackage\ValueObjects\StepResult;

test('success creates successful result', function () {
    $result = StepResult::success(['key' => 'value'], 'Success message');

    expect($result->success)->toBeTrue();
    expect($result->isSuccess)->toBeTrue();
    expect($result->data)->toBe(['key' => 'value']);
    expect($result->errors)->toBe([]);
    expect($result->hasErrors)->toBeFalse();
    expect($result->message)->toBe('Success message');
    expect($result->redirectTo)->toBeNull();
});

test('success with default parameters', function () {
    $result = StepResult::success();

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->message)->toBeNull();
});

test('failure creates failed result', function () {
    $errors = ['field' => ['Error message']];
    $result = StepResult::failure($errors, 'Failure message');

    expect($result->success)->toBeFalse();
    expect($result->isSuccess)->toBeFalse();
    expect($result->data)->toBe([]);
    expect($result->errors)->toBe($errors);
    expect($result->hasErrors)->toBeTrue();
    expect($result->message)->toBe('Failure message');
    expect($result->redirectTo)->toBeNull();
});

test('failure with default message', function () {
    $result = StepResult::failure(['field' => ['Error']]);

    expect($result->success)->toBeFalse();
    expect($result->message)->toBeNull();
});

test('redirect creates result with redirect url', function () {
    $result = StepResult::redirect('/custom-url', ['data' => 'value']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['data' => 'value']);
    expect($result->errors)->toBe([]);
    expect($result->message)->toBeNull();
    expect($result->redirectTo)->toBe('/custom-url');
    expect($result->shouldRedirect())->toBeTrue();
});

test('redirect with default data', function () {
    $result = StepResult::redirect('/url');

    expect($result->data)->toBe([]);
    expect($result->shouldRedirect())->toBeTrue();
});

test('getErrors returns errors array', function () {
    $errors = ['field1' => ['Error 1'], 'field2' => ['Error 2']];
    $result = StepResult::failure($errors);

    expect($result->getErrors())->toBe($errors);
});

test('shouldRedirect returns false when no redirect', function () {
    $result = StepResult::success();

    expect($result->shouldRedirect())->toBeFalse();
});

test('isSuccess property hook works correctly', function () {
    $success = StepResult::success();
    $failure = StepResult::failure(['error']);

    expect($success->isSuccess)->toBe($success->success);
    expect($failure->isSuccess)->toBe($failure->success);
});

test('hasErrors property hook works correctly', function () {
    $withErrors = StepResult::failure(['field' => ['Error']]);
    $withoutErrors = StepResult::success();

    expect($withErrors->hasErrors)->toBeTrue();
    expect($withoutErrors->hasErrors)->toBeFalse();
});

test('result is immutable', function () {
    $result = StepResult::success(['initial' => 'data']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['initial' => 'data']);
});

test('multiple failures with different error structures', function () {
    $simpleError = StepResult::failure(['error' => 'Simple error']);
    $complexError = StepResult::failure([
        'name' => ['Name is required', 'Name must be at least 3 characters'],
        'email' => ['Invalid email format'],
    ]);

    expect($simpleError->hasErrors)->toBeTrue();
    expect($simpleError->getErrors())->toHaveKey('error');

    expect($complexError->hasErrors)->toBeTrue();
    expect($complexError->getErrors())->toHaveKeys(['name', 'email']);
    expect(count($complexError->getErrors()['name']))->toBe(2);
});
