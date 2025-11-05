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

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function initialize(string $wizardId, array $config = []): void
```

</div>

Initialize a new wizard instance.

**Parameters:**
- `$wizardId` - The wizard identifier (e.g., 'onboarding', 'checkout')
- `$config` - Optional configuration overrides

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
use Invelity\WizardPackage\Facades\Wizard;

Wizard::initialize('onboarding');
```

</div>

---

### Get Current Step

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getCurrentStep(): ?WizardStepInterface
```

</div>

Returns the current active step, or null if not set.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$step = Wizard::getCurrentStep();
echo $step?->getTitle(); // "Personal Information"
```

</div>

---

### Get Specific Step

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getStep(string $stepId): WizardStepInterface
```

</div>

Get a step by its ID.

**Parameters:**
- `$stepId` - The step identifier

**Throws:** `\InvalidArgumentException` if step not found

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$step = Wizard::getStep('personal-info');
```

</div>

---

### Process Step

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function processStep(string $stepId, array $data): StepResult
```

</div>

Process and validate step data.

**Parameters:**
- `$stepId` - The step to process
- `$data` - The step data to validate and process

**Returns:** `StepResult` - Contains success/failure status and messages

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$result = Wizard::processStep('personal-info', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

if ($result->isSuccess()) {
    echo $result->message(); // "Personal information saved"
}
```

</div>

---

### Navigation Methods

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function navigateToStep(string $stepId): void
public function getNextStep(): ?WizardStepInterface
public function getPreviousStep(): ?WizardStepInterface
public function canAccessStep(string $stepId): bool
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$next = Wizard::getNextStep();
$prev = Wizard::getPreviousStep();

if (Wizard::canAccessStep('payment')) {
    Wizard::navigateToStep('payment');
}
```

</div>

---

### Get Progress

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getProgress(): WizardProgressValue
```

</div>

Returns wizard completion progress.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$progress = Wizard::getProgress();

echo $progress->completionPercentage(); // 33
echo $progress->completedSteps(); // 1
echo $progress->totalSteps(); // 3
echo $progress->isComplete() ? 'Done' : 'In Progress';
```

</div>

---

### Get All Data

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getAllData(): array
```

</div>

Returns all wizard data from completed steps.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$data = Wizard::getAllData();
// [
//     'personal-info' => ['name' => 'John Doe'],
//     'contact-details' => ['email' => 'john@example.com'],
// ]
```

</div>

---

### Complete Wizard

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function complete(): StepResult
```

</div>

Mark wizard as complete and trigger completion events.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$result = Wizard::complete();

if ($result->isSuccess()) {
    // Wizard completed successfully
}
```

</div>

---

### Reset Wizard

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function reset(): void
```

</div>

Reset wizard to initial state, clearing all progress.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
Wizard::reset();
```

</div>

---

### Skip Step

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function skipStep(string $stepId): void
```

</div>

Skip an optional step.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
Wizard::skipStep('newsletter-preferences');
```

</div>

---

## WizardStepInterface

Interface for individual wizard steps.

### Step Properties

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getId(): string
public function getTitle(): string
public function getOrder(): int
public function isOptional(): bool
public function canSkip(): bool
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$step = Wizard::getCurrentStep();

echo $step->getId(); // "personal-info"
echo $step->getTitle(); // "Personal Information"
echo $step->getOrder(); // 1
echo $step->isOptional() ? 'Optional' : 'Required';
```

</div>

---

### Validation Rules

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function rules(): array
```

</div>

Returns Laravel validation rules for the step.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### Process Step Data

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function process(StepData $data): StepResult
```

</div>

Process validated step data.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### Lifecycle Hooks

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function beforeProcess(StepData $data): void
public function afterProcess(StepResult $result): void
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### Conditional Logic

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function shouldSkip(array $wizardData): bool
```

</div>

Determine if step should be skipped based on wizard data.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function shouldSkip(array $wizardData): bool
{
    return isset($wizardData['subscription']['plan']) 
        && $wizardData['subscription']['plan'] === 'free';
}
```

</div>

---

### Dependencies

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getDependencies(): array
```

</div>

Return step IDs that must be completed first.

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function getDependencies(): array
{
    return ['personal-info', 'contact-details'];
}
```

</div>

---

## StepResult

Value object representing step processing result.

### Success Result

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
StepResult::success(
    array $data = [],
    string $message = '',
    array $meta = []
): StepResult
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
return StepResult::success(
    data: ['user_id' => 123],
    message: 'Profile created successfully'
);
```

</div>

---

### Failure Result

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
StepResult::failure(
    string $message,
    array $errors = []
): StepResult
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
return StepResult::failure(
    message: 'Payment failed',
    errors: ['card' => ['Invalid card number']]
);
```

</div>

---

### Redirect Result

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
StepResult::redirect(
    string $url,
    array $data = []
): StepResult
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
return StepResult::redirect('/external-payment', [
    'session_id' => 'abc123'
]);
```

</div>

---

### Check Result

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$result->isSuccess(): bool
$result->isFailure(): bool
$result->isRedirect(): bool
$result->message(): string
$result->data(): array
$result->errors(): array
```

</div>

---

## StepData

Value object for accessing validated step data.

### Get Data

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$data->get(string $key, mixed $default = null): mixed
$data->all(): array
$data->has(string $key): bool
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

## WizardProgressValue

Value object representing wizard completion state.

### Methods

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$progress->completionPercentage(): int
$progress->completedSteps(): int
$progress->totalSteps(): int
$progress->isComplete(): bool
$progress->remainingSteps(): int
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
$progress = Wizard::getProgress();

echo "Progress: {$progress->completionPercentage()}%";
echo "Completed: {$progress->completedSteps()}/{$progress->totalSteps()}";
echo "Remaining: {$progress->remainingSteps()} steps";
```

</div>

---

## WizardStorageInterface

Interface for wizard data persistence.

### Storage Methods

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
public function put(string $key, array $data): void
public function get(string $key): ?array
public function exists(string $key): bool
public function forget(string $key): void
public function update(string $key, string $field, mixed $value): void
```

</div>

**Example:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
use Invelity\WizardPackage\Contracts\WizardStorageInterface;

$storage = app(WizardStorageInterface::class);

$storage->put('wizard.onboarding', ['step' => 'personal-info']);
$data = $storage->get('wizard.onboarding');
$storage->update('wizard.onboarding', 'step', 'contact-details');
$storage->forget('wizard.onboarding');
```

</div>

---

## Events

### WizardStarted

Fired when wizard is initialized.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### StepCompleted

Fired when a step is successfully completed.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### StepSkipped

Fired when an optional step is skipped.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
use Invelity\WizardPackage\Events\StepSkipped;
```

</div>

---

### WizardCompleted

Fired when wizard is completed.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
use Invelity\WizardPackage\Events\WizardCompleted;

class WizardCompletedListener
{
    public function handle(WizardCompleted $event): void
    {
        // All wizard data is available
        $data = $event->allData;
        
        // Create final records with relationships
        $user = User::create([
            'name' => $data['steps']['personal-info']['name'],
            'email' => $data['steps']['personal-info']['email'],
        ]);
        
        $user->preferences()->create([
            'theme' => $data['steps']['preferences']['theme'],
        ]);
        
        Log::info('Wizard completed', [
            'wizard_id' => $event->wizardId,
            'completed_at' => $event->completedAt,
        ]);
    }
}
```

</div>

---

## Wizard Progress Status

The `wizard_progress` table tracks wizard state with three statuses:

### Status Values

**`in_progress`** (default)
- Set when wizard is initialized
- Remains during step completion

**`completed`**
- Set when `complete()` is called
- Triggers `WizardCompleted` event
- Sets `completed_at` timestamp

**`abandoned`**
- Must be set manually via `markAsAbandoned()`
- Used for analytics/cleanup
- Not automatically triggered

### Managing Status

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
use Invelity\WizardPackage\Models\WizardProgress;

// Mark as completed (automatic when wizard finishes)
$progress = WizardProgress::where('wizard_id', 'registration')->first();
$progress->markAsCompleted();

// Mark as abandoned (manual)
$progress->markAsAbandoned();

// Check status
if ($progress->isComplete()) {
    // Wizard finished
}

if ($progress->isAbandoned()) {
    // User left wizard
}
```

</div>

### Cleanup Command

Create a scheduled command to clean up old abandoned wizards:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
// app/Console/Commands/CleanupAbandonedWizards.php
use Invelity\WizardPackage\Models\WizardProgress;

WizardProgress::where('status', 'in_progress')
    ->where('last_activity_at', '<', now()->subDays(30))
    ->update(['status' => 'abandoned']);
```

</div>

---

## HTTP Responses

All wizard routes return JSON responses in this format:

### Success Response

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

### Error Response

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

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

</div>

---

## Next Steps

- [View Examples](examples)
- [Learn Testing](testing)
- [Back to Creating Wizards](creating-wizards)
