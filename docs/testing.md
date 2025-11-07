---
layout: default
title: Testing
nav_order: 7
---

# Testing Wizards

Learn how to test your wizard implementations with Pest PHP.

---

## Testing Setup

This package uses [Pest PHP](https://pestphp.com/) for testing. All tests use modern Pest syntax with `test()`, `expect()`, and `beforeEach()`.

### Basic Test Structure

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('wizard completes successfully', function () {
    Wizard::initialize('onboarding');
    
    $result = Wizard::processStep('personal-info', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    expect($result->isSuccess())->toBeTrue();
});
```

---

## Testing Complete Wizard Flows

Test a wizard from start to finish:

```php
<?php

use Invelity\WizardPackage\Contracts\WizardManagerInterface;

test('complete wizard flow from start to finish', function () {
    $manager = app(WizardManagerInterface::class);
    
    $manager->initialize('onboarding');
    
    $currentStep = $manager->getCurrentStep();
    expect($currentStep->getId())->toBe('personal-info');
    
    $result = $manager->processStep('personal-info', [
        'name' => 'John Doe'
    ]);
    expect($result->success)->toBeTrue();
    
    $progress = $manager->getProgress();
    expect($progress->completedSteps)->toBe(1)
        ->and($progress->totalSteps)->toBe(3);
    
    $nextStep = $manager->getNextStep();
    expect($nextStep)->not->toBeNull();
    
    $manager->processStep('contact-details', [
        'email' => 'john@example.com'
    ]);
    
    $progress = $manager->getProgress();
    expect($progress->isComplete)->toBeTrue();
    
    $result = $manager->complete();
    expect($result->success)->toBeTrue();
    
    $allData = $manager->getAllData();
    expect($allData)->toHaveKey('personal-info')
        ->and($allData['personal-info']['name'])->toBe('John Doe');
});
```

---

## Testing Step Validation

Test that validation rules work correctly:

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;

test('step validates required fields', function () {
    Wizard::initialize('onboarding');
    
    $result = Wizard::processStep('personal-info', [
        'name' => '',
    ]);
    
    expect($result->isFailure())->toBeTrue()
        ->and($result->errors())->toHaveKey('name');
});

test('step validates email format', function () {
    Wizard::initialize('onboarding');
    
    $result = Wizard::processStep('contact-details', [
        'email' => 'invalid-email',
    ]);
    
    expect($result->isFailure())->toBeTrue()
        ->and($result->errors())->toHaveKey('email');
});
```

---

## Testing Navigation

Test wizard navigation behavior:

```php
<?php

use Invelity\WizardPackage\Contracts\WizardManagerInterface;

test('wizard prevents skipping forward without completing previous steps', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    expect($manager->canAccessStep('personal-info'))->toBeTrue()
        ->and($manager->canAccessStep('contact-details'))->toBeFalse();
});

test('wizard allows navigation back to completed steps', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    expect($manager->canAccessStep('personal-info'))->toBeTrue();
    
    $manager->navigateToStep('personal-info');
    
    $currentStep = $manager->getCurrentStep();
    expect($currentStep->getId())->toBe('personal-info');
});

test('getNextStep returns null when on last step', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);
    
    $manager->navigateToStep('contact-details');
    
    expect($manager->getNextStep())->toBeNull();
});
```

---

## Testing Optional Steps

Test optional and skippable steps:

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;

test('optional step can be skipped', function () {
    Wizard::initialize('onboarding');
    
    Wizard::processStep('personal-info', ['name' => 'John']);
    
    Wizard::skipStep('preferences');
    
    $progress = Wizard::getProgress();
    expect($progress->completedSteps)->toBe(1);
    
    $currentStep = Wizard::getCurrentStep();
    expect($currentStep->getId())->toBe('email-verification');
});
```

---

## Testing Conditional Steps

Test steps that should be skipped based on data:

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;

test('payment step is skipped for free plan', function () {
    Wizard::initialize('checkout');
    
    Wizard::processStep('plan-selection', ['plan' => 'free']);
    
    $step = Wizard::getStep('payment');
    $wizardData = Wizard::getAllData();
    
    expect($step->shouldSkip($wizardData))->toBeTrue();
});
```

---

## Testing with Different Storage Drivers

Test with session storage:

```php
<?php

beforeEach(function () {
    config(['wizard-package.storage.driver' => 'session']);
});

test('wizard data persists in session', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    $data = session('wizard.onboarding');
    expect($data)->toHaveKey('personal-info');
});
```

Test with database storage:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wizard-package.storage.driver' => 'database']);
    $this->artisan('migrate');
});

test('wizard data persists in database', function () {
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    $this->assertDatabaseHas('wizard_progress', [
        'wizard_id' => 'onboarding',
    ]);
});
```

---

## Testing HTTP Endpoints

Test wizard controller endpoints:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('POST /wizard/{wizard}/{step} processes step data', function () {
    $response = $this->postJson('/wizard/onboarding/personal-info', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Personal information saved successfully',
        ]);
});

test('GET /wizard/{wizard} returns current step', function () {
    $this->postJson('/wizard/onboarding/personal-info', [
        'name' => 'John Doe',
    ]);
    
    $response = $this->getJson('/wizard/onboarding');
    
    $response->assertSuccessful()
        ->assertJsonStructure([
            'step',
            'progress',
            'navigation',
        ]);
});

test('validation errors are returned correctly', function () {
    $response = $this->postJson('/wizard/onboarding/personal-info', [
        'name' => '',
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
```

---

## Testing Events

Test that wizard events are fired:

```php
<?php

use Illuminate\Support\Facades\Event;
use Invelity\WizardPackage\Events\WizardStarted;
use Invelity\WizardPackage\Events\StepCompleted;
use Invelity\WizardPackage\Events\WizardCompleted;

test('WizardStarted event is fired on initialization', function () {
    Event::fake();
    
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    Event::assertDispatched(WizardStarted::class, function ($event) {
        return $event->wizardId === 'onboarding';
    });
});

test('StepCompleted event is fired when step processes successfully', function () {
    Event::fake();
    
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    
    Event::assertDispatched(StepCompleted::class, function ($event) {
        return $event->stepId === 'personal-info';
    });
});

test('WizardCompleted event is fired on wizard completion', function () {
    Event::fake();
    
    $manager = app(WizardManagerInterface::class);
    $manager->initialize('onboarding');
    
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);
    $manager->complete();
    
    Event::assertDispatched(WizardCompleted::class);
});
```

---

## Testing Custom Steps

Test your custom step implementations:

```php
<?php

use App\Wizards\Steps\PaymentStep;
use Invelity\WizardPackage\ValueObjects\StepData;

test('PaymentStep processes card payment', function () {
    $step = new PaymentStep();
    
    $data = new StepData([
        'payment_method' => 'card',
        'card_number' => '4111111111111111',
        'card_exp' => '12/25',
        'card_cvv' => '123',
    ]);
    
    $result = $step->process($data);
    
    expect($result->isSuccess())->toBeTrue()
        ->and($result->data())->toHaveKey('payment_id');
});

test('PaymentStep validates required card fields', function () {
    $step = new PaymentStep();
    
    $rules = $step->rules();
    
    expect($rules)->toHaveKey('card_number')
        ->and($rules['card_number'])->toContain('required_if:payment_method,card');
});

test('PaymentStep has dependencies on previous steps', function () {
    $step = new PaymentStep();
    
    $dependencies = $step->getDependencies();
    
    expect($dependencies)->toContain('cart-review')
        ->and($dependencies)->toContain('shipping-address');
});
```

---

## Testing Artisan Commands

Test wizard generation commands:

```php
<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(app_path('Wizards'))) {
        File::deleteDirectory(app_path('Wizards'));
    }
});

test('wizard:make creates wizard class', function () {
    $this->artisan('wizard:make', ['name' => 'Onboarding'])
        ->expectsOutput('✓ Wizard class created')
        ->assertSuccessful();
    
    expect(File::exists(app_path('Wizards/Onboarding.php')))->toBeTrue();
});

test('wizard:make-step creates step class and form request', function () {
    $this->artisan('wizard:make-step', [
        'name' => 'PersonalInfo',
        '--wizard' => 'onboarding',
        '--order' => 1,
    ])
        ->expectsQuestion('What is the step title?', 'Personal Information')
        ->expectsConfirmation('Is this step optional?', 'no')
        ->assertSuccessful();
    
    expect(File::exists(app_path('Wizards/Steps/PersonalInfoStep.php')))->toBeTrue();
    expect(File::exists(app_path('Http/Requests/Wizards/PersonalInfoRequest.php')))->toBeTrue();
});
```

---

## Testing Progress Tracking

Test progress calculation:

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;

test('progress calculates completion percentage correctly', function () {
    Wizard::initialize('onboarding');
    
    $progress = Wizard::getProgress();
    expect($progress->completionPercentage())->toBe(0);
    
    Wizard::processStep('personal-info', ['name' => 'John']);
    
    $progress = Wizard::getProgress();
    expect($progress->completionPercentage())->toBe(33);
    
    Wizard::processStep('contact-details', ['email' => 'john@example.com']);
    
    $progress = Wizard::getProgress();
    expect($progress->completionPercentage())->toBe(66);
});

test('progress tracks completed steps', function () {
    Wizard::initialize('onboarding');
    
    Wizard::processStep('personal-info', ['name' => 'John']);
    
    $progress = Wizard::getProgress();
    expect($progress->completedSteps())->toBe(1)
        ->and($progress->totalSteps())->toBe(3)
        ->and($progress->remainingSteps())->toBe(2);
});
```

---

## Mock External Services

Mock external dependencies in step processing:

```php
<?php

use App\Services\PaymentGateway;
use Mockery;

test('payment step handles gateway failures gracefully', function () {
    $mockGateway = Mockery::mock(PaymentGateway::class);
    $mockGateway->shouldReceive('charge')
        ->andThrow(new \Exception('Payment declined'));
    
    $this->app->instance(PaymentGateway::class, $mockGateway);
    
    $step = app(\App\Wizards\Steps\PaymentStep::class);
    
    $data = new \Invelity\WizardPackage\ValueObjects\StepData([
        'payment_method' => 'card',
        'card_number' => '4111111111111111',
    ]);
    
    $result = $step->process($data);
    
    expect($result->isFailure())->toBeTrue()
        ->and($result->message())->toContain('Payment declined');
});
```

---

## Architecture Tests

Test architecture constraints with Pest:

```php
<?php

arch('strict types are declared')
    ->expect('Invelity\WizardPackage')
    ->toUseStrictTypes();

arch('contracts are interfaces')
    ->expect('Invelity\WizardPackage\Contracts')
    ->toBeInterfaces();

arch('value objects are readonly')
    ->expect('Invelity\WizardPackage\ValueObjects')
    ->classes()
    ->toBeReadonly();

arch('events are final')
    ->expect('Invelity\WizardPackage\Events')
    ->classes()
    ->toBeFinal();
```

---

## Running Tests

### Run all tests

```bash
composer test
```

### Run specific test file

```bash
vendor/bin/pest tests/Unit/WizardManagerTest.php
```

### Run with coverage

```bash
vendor/bin/pest --coverage
```

### Run specific test by name

```bash
vendor/bin/pest --filter="wizard completes successfully"
```

---

## Testing Blade Components

Test that components render correctly:

```php
<?php

use Invelity\WizardPackage\Components\ProgressBar;

test('progress bar calculates percentage correctly', function () {
    $steps = [
        ['id' => 'step1'],
        ['id' => 'step2'],
        ['id' => 'step3'],
    ];
    
    $component = new ProgressBar($steps, 'step2');
    
    expect($component->percentage)->toBe(66); // 2/3 * 100
});

test('layout component accepts title', function () {
    $component = new \Invelity\WizardPackage\Components\Layout('My Wizard');
    
    expect($component->title)->toBe('My Wizard');
});
```

---

## Testing Vue Composable

Mock the composable for Vue component tests:

```typescript
import { vi } from 'vitest';
import { useWizard } from '@/composables/useWizard';

vi.mock('@/composables/useWizard');

test('wizard component initializes on mount', async () => {
    const mockInitialize = vi.fn();
    
    (useWizard as any).mockReturnValue({
        state: { loading: false, steps: [] },
        currentStep: null,
        initialize: mockInitialize,
        submitStep: vi.fn(),
    });
    
    const wrapper = mount(WizardComponent);
    
    await wrapper.vm.$nextTick();
    
    expect(mockInitialize).toHaveBeenCalled();
});
```

---

## Best Practices

1. **Use Pest syntax** - Use `test()`, `expect()`, `beforeEach()` instead of PHPUnit classes
2. **Test real flows** - Test actual wizard usage, not implementation details
3. **Use descriptive test names** - Test names should describe behavior
4. **Test edge cases** - Test validation errors, navigation boundaries, optional steps
5. **Mock external services** - Don't make real API calls in tests
6. **Use database transactions** - Use `RefreshDatabase` trait for clean state
7. **Test events** - Verify lifecycle events are fired correctly

---

## Next Steps

- [View API Reference](api-reference)
- [See Examples](examples)
- [Back to Home](index)
