---
layout: default
title: API Reference
nav_order: 5
---

# API Reference

Complete API documentation for Laravel Headless Wizard.

---

## WizardManagerInterface

The main interface for managing wizard state and navigation.

### Initialize a Wizard

```php
public function initialize(string $wizardId, array $config = []): void
```

Initialize a new wizard instance.

**Parameters:**
- `$wizardId` - The wizard identifier (e.g., 'onboarding', 'checkout')
- `$config` - Optional configuration overrides

**Example:**
```php
use Invelity\WizardPackage\Facades\Wizard;

Wizard::initialize('onboarding');
```

---

### Get Current Step

```php
public function getCurrentStep(): ?WizardStepInterface
```

Returns the current active step, or null if not set.

**Example:**
```php
$step = Wizard::getCurrentStep();
echo $step?->getTitle(); // "Personal Information"
```

---

### Get Specific Step

```php
public function getStep(string $stepId): WizardStepInterface
```

Get a step by its ID.

**Parameters:**
- `$stepId` - The step identifier

**Throws:** `\InvalidArgumentException` if step not found

**Example:**
```php
$step = Wizard::getStep('personal-info');
```

---

### Process Step

```php
public function processStep(string $stepId, array $data): StepResult
```

Process and validate step data.

**Parameters:**
- `$stepId` - The step to process
- `$data` - The step data to validate and process

**Returns:** `StepResult` - Contains success/failure status and messages

**Example:**
```php
$result = Wizard::processStep('personal-info', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

if ($result->isSuccess()) {
    echo $result->message(); // "Personal information saved"
}
```

---

### Navigation Methods

```php
public function navigateToStep(string $stepId): void
public function getNextStep(): ?WizardStepInterface
public function getPreviousStep(): ?WizardStepInterface
public function canAccessStep(string $stepId): bool
```

**Example:**
```php
$next = Wizard::getNextStep();
$prev = Wizard::getPreviousStep();

if (Wizard::canAccessStep('payment')) {
    Wizard::navigateToStep('payment');
}
```

---

### Get Progress

```php
public function getProgress(): WizardProgressValue
```

Returns wizard completion progress.

**Example:**
```php
$progress = Wizard::getProgress();

echo $progress->completionPercentage(); // 33
echo $progress->completedSteps(); // 1
echo $progress->totalSteps(); // 3
echo $progress->isComplete() ? 'Done' : 'In Progress';
```

---

### Get All Data

```php
public function getAllData(): array
```

Returns all wizard data from completed steps.

**Example:**
```php
$data = Wizard::getAllData();
// [
//     'personal-info' => ['name' => 'John Doe'],
//     'contact-details' => ['email' => 'john@example.com'],
// ]
```

---

### Complete Wizard

```php
public function complete(): StepResult
```

Mark wizard as complete and trigger completion events.

**Example:**
```php
$result = Wizard::complete();

if ($result->isSuccess()) {
    // Wizard completed successfully
}
```

---

### Reset Wizard

```php
public function reset(): void
```

Reset wizard to initial state, clearing all progress.

**Example:**
```php
Wizard::reset();
```

---

### Skip Step

```php
public function skipStep(string $stepId): void
```

Skip an optional step.

**Example:**
```php
Wizard::skipStep('newsletter-preferences');
```

---

## WizardStepInterface

Interface for individual wizard steps.

### Step Properties

```php
public function getId(): string
public function getTitle(): string
public function getOrder(): int
public function isOptional(): bool
public function canSkip(): bool
```

**Example:**
```php
$step = Wizard::getCurrentStep();

echo $step->getId(); // "personal-info"
echo $step->getTitle(); // "Personal Information"
echo $step->getOrder(); // 1
echo $step->isOptional() ? 'Optional' : 'Required';
```

---

### Validation Rules

```php
public function rules(): array
```

Returns Laravel validation rules for the step.

**Example:**
```php
class PersonalInfoStep extends AbstractStep
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }
}
```

---

### Process Step Data

```php
public function process(StepData $data): StepResult
```

Process validated step data.

**Example:**
```php
public function process(StepData $data): StepResult
{
    $user = User::create([
        'name' => $data->get('name'),
        'email' => $data->get('email'),
    ]);
    
    return StepResult::success(
        data: ['user_id' => $user->id],
        message: 'User created successfully'
    );
}
```

---

### Lifecycle Hooks

```php
public function beforeProcess(StepData $data): void
public function afterProcess(StepResult $result): void
```

**Example:**
```php
public function beforeProcess(StepData $data): void
{
    Log::info('Processing step', ['data' => $data->all()]);
}

public function afterProcess(StepResult $result): void
{
    if ($result->isSuccess()) {
        Cache::forget('wizard-temp-data');
    }
}
```

---

### Conditional Logic

```php
public function shouldSkip(array $wizardData): bool
```

Determine if step should be skipped based on wizard data.

**Example:**
```php
public function shouldSkip(array $wizardData): bool
{
    return isset($wizardData['subscription']['plan']) 
        && $wizardData['subscription']['plan'] === 'free';
}
```

---

### Dependencies

```php
public function getDependencies(): array
```

Return step IDs that must be completed first.

**Example:**
```php
public function getDependencies(): array
{
    return ['personal-info', 'contact-details'];
}
```

---

## StepResult

Value object representing step processing result.

### Success Result

```php
StepResult::success(
    array $data = [],
    string $message = '',
    array $meta = []
): StepResult
```

**Example:**
```php
return StepResult::success(
    data: ['user_id' => 123],
    message: 'Profile created successfully'
);
```

---

### Failure Result

```php
StepResult::failure(
    string $message,
    array $errors = []
): StepResult
```

**Example:**
```php
return StepResult::failure(
    message: 'Payment failed',
    errors: ['card' => ['Invalid card number']]
);
```

---

### Redirect Result

```php
StepResult::redirect(
    string $url,
    array $data = []
): StepResult
```

**Example:**
```php
return StepResult::redirect('/external-payment', [
    'session_id' => 'abc123'
]);
```

---

### Check Result

```php
$result->isSuccess(): bool
$result->isFailure(): bool
$result->isRedirect(): bool
$result->message(): string
$result->data(): array
$result->errors(): array
```

---

## StepData

Value object for accessing validated step data.

### Get Data

```php
$data->get(string $key, mixed $default = null): mixed
$data->all(): array
$data->has(string $key): bool
```

**Example:**
```php
public function process(StepData $data): StepResult
{
    $name = $data->get('name');
    $email = $data->get('email', 'no-reply@example.com');
    
    if ($data->has('phone')) {
        // Process phone number
    }
    
    $allData = $data->all();
}
```

---

## WizardProgressValue

Value object representing wizard completion state.

### Methods

```php
$progress->completionPercentage(): int
$progress->completedSteps(): int
$progress->totalSteps(): int
$progress->isComplete(): bool
$progress->remainingSteps(): int
```

**Example:**
```php
$progress = Wizard::getProgress();

echo "Progress: {$progress->completionPercentage()}%";
echo "Completed: {$progress->completedSteps()}/{$progress->totalSteps()}";
echo "Remaining: {$progress->remainingSteps()} steps";
```

---

## WizardStorageInterface

Interface for wizard data persistence.

### Storage Methods

```php
public function put(string $key, array $data): void
public function get(string $key): ?array
public function exists(string $key): bool
public function forget(string $key): void
public function update(string $key, string $field, mixed $value): void
```

**Example:**
```php
use Invelity\WizardPackage\Contracts\WizardStorageInterface;

$storage = app(WizardStorageInterface::class);

$storage->put('wizard.onboarding', ['step' => 'personal-info']);
$data = $storage->get('wizard.onboarding');
$storage->update('wizard.onboarding', 'step', 'contact-details');
$storage->forget('wizard.onboarding');
```

---

## Events

### WizardStarted

Fired when wizard is initialized.

```php
use Invelity\WizardPackage\Events\WizardStarted;

class WizardStartedListener
{
    public function handle(WizardStarted $event): void
    {
        Log::info('Wizard started', [
            'wizard_id' => $event->wizardId,
            'user_id' => $event->userId,
            'session_id' => $event->sessionId,
        ]);
    }
}
```

---

### StepCompleted

Fired when a step is successfully completed.

```php
use Invelity\WizardPackage\Events\StepCompleted;

class StepCompletedListener
{
    public function handle(StepCompleted $event): void
    {
        Log::info('Step completed', [
            'wizard_id' => $event->wizardId,
            'step_id' => $event->stepId,
            'progress' => $event->progress,
        ]);
    }
}
```

---

### StepSkipped

Fired when an optional step is skipped.

```php
use Invelity\WizardPackage\Events\StepSkipped;
```

---

### WizardCompleted

Fired when wizard is completed.

```php
use Invelity\WizardPackage\Events\WizardCompleted;
```

---

## HTTP Responses

All wizard routes return JSON responses in this format:

### Success Response

```json
{
    "success": true,
    "message": "Step completed successfully",
    "data": {
        "user_id": 123
    },
    "step": {
        "id": "personal-info",
        "title": "Personal Information",
        "order": 1,
        "is_optional": false
    },
    "next_step": {
        "id": "contact-details",
        "title": "Contact Details"
    },
    "progress": {
        "completed": 1,
        "total": 3,
        "percentage": 33,
        "is_complete": false
    },
    "navigation": {
        "can_go_back": false,
        "can_go_forward": true,
        "previous_step": null,
        "next_step": {
            "id": "contact-details",
            "title": "Contact Details"
        }
    }
}
```

---

### Error Response

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "phone": ["The phone format is invalid."]
    }
}
```

---

## Next Steps

- [View Examples](examples)
- [Learn Testing](testing)
- [Back to Creating Wizards](creating-wizards)
