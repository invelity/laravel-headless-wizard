---
layout: default
title: Creating Wizards
nav_order: 4
---

# Creating Wizards

Learn how to create multi-step wizards from scratch.

---

## Quick Start

### 1. Generate a Wizard

```bash
php artisan wizard:make Onboarding
```

**Interactive prompt:**
```
✔ What is the wizard name? › Onboarding
✓ Wizard class created: app/Wizards/Onboarding.php
✓ Registered in config: config/wizard.php
✓ Config cache cleared
```

### 2. Generate Steps

```bash
php artisan wizard:make-step --wizard=onboarding
```

**Interactive prompts:**
```
✔ What is the step name? › PersonalInfo
✔ What is the step title? › Personal Information  
✔ What is the step order? › 1
✔ Is this step optional? › No

✓ Step class created: app/Wizards/Steps/PersonalInfoStep.php
✓ FormRequest created: app/Http/Requests/Wizards/PersonalInfoRequest.php
✓ Registered in wizard: onboarding
```

---

## Wizard Structure

A wizard consists of:

1. **Wizard Class** - Orchestrates the overall flow
2. **Step Classes** - Individual wizard steps
3. **Form Requests** - Laravel validation for each step
4. **Configuration** - Wizard settings in config file

---

## Creating Custom Steps

### Step Class Example

```php
<?php

namespace App\Wizards\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PersonalInfoStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'personal-info',
            title: 'Personal Information',
            order: 1,
            optional: false
        );
    }

    public function process(StepData $data): StepResult
    {
        // Process the step data
        $name = $data->get('name');
        $email = $data->get('email');
        
        // Your business logic here
        // For example, create a user record, send email, etc.
        
        return StepResult::success('Personal information saved!');
    }

    public function shouldSkip(array $wizardData): bool
    {
        // Skip this step if email already exists
        return isset($wizardData['email_verified']) && $wizardData['email_verified'];
    }

    public function getDependencies(): array
    {
        // This step has no dependencies
        return [];
    }
}
```

### Form Request Example

```php
<?php

namespace App\Http\Requests\Wizards;

use Illuminate\Foundation\Http\FormRequest;

class PersonalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'date_of_birth' => ['required', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Phone number must be 10 digits.',
        ];
    }
}
```

---

## Optional Steps

Make a step optional by passing `optional: true`:

```php
public function __construct()
{
    parent::__construct(
        id: 'newsletter',
        title: 'Newsletter Preferences',
        order: 3,
        optional: true // Users can skip this step
    );
}
```

---

## Conditional Steps

Skip steps based on wizard data:

```php
public function shouldSkip(array $wizardData): bool
{
    // Skip billing if user selected free plan
    return $wizardData['plan_type'] === 'free';
}
```

---

## Step Dependencies

Require other steps to be completed first:

```php
public function getDependencies(): array
{
    // This step requires personal-info and address to be completed
    return ['personal-info', 'address'];
}
```

---

## Processing Step Data

The `process()` method is called after validation:

```php
public function process(StepData $data): StepResult
{
    try {
        // Access validated data
        $name = $data->get('name');
        $email = $data->get('email');
        
        // Your business logic
        User::create([
            'name' => $name,
            'email' => $email,
        ]);
        
        // Return success
        return StepResult::success('User created successfully!');
        
    } catch (\Exception $e) {
        // Return failure with error message
        return StepResult::failure($e->getMessage());
    }
}
```

### StepResult Options

```php
// Success
return StepResult::success('Step completed!');

// Success with redirect
return StepResult::redirect('/custom-route', ['key' => 'value']);

// Failure
return StepResult::failure('Something went wrong');

// Failure with validation errors
return StepResult::failure('Validation failed', [
    'email' => ['Email already exists'],
]);
```

---

## Accessing Wizard Data

Get data from previous steps:

```php
use Invelity\WizardPackage\Traits\HasWizardSteps;

class PaymentStep extends AbstractStep
{
    use HasWizardSteps;
    
    public function process(StepData $data): StepResult
    {
        // Get data from previous steps
        $userEmail = $this->getWizardData('personal-info.email');
        $planType = $this->getWizardData('subscription.plan');
        
        // Process payment
        // ...
        
        return StepResult::success();
    }
}
```

---

## Step Lifecycle Events

Listen to step events:

```php
// In your EventServiceProvider
protected $listen = [
    \Invelity\WizardPackage\Events\StepCompleted::class => [
        SendStepCompletedNotification::class,
    ],
    \Invelity\WizardPackage\Events\StepSkipped::class => [
        LogSkippedStep::class,
    ],
];
```

Available events:
- `WizardStarted` - When wizard is initialized
- `StepCompleted` - When a step is successfully completed
- `StepSkipped` - When an optional step is skipped
- `WizardCompleted` - When all steps are finished

---

## Using the Facade

### Initialize a Wizard

```php
use Invelity\WizardPackage\Facades\Wizard;

// Initialize
Wizard::initialize('onboarding');

// Get current step
$step = Wizard::getCurrentStep();

// Process step
$result = Wizard::processStep('personal-info', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Navigate
$nextStep = Wizard::getNextStep();
$prevStep = Wizard::getPreviousStep();

// Check progress
$progress = Wizard::getProgress();
echo $progress->completionPercentage(); // 33%

// Complete wizard
Wizard::complete();
```

---

## Frontend Integration

### React/Vue/Inertia Example

```javascript
// Fetch wizard state
const response = await fetch('/wizard/onboarding/personal-info');
const { step, navigation, progress } = await response.json();

// Submit step data
const result = await fetch('/wizard/onboarding/personal-info', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: 'John Doe',
        email: 'john@example.com'
    })
});

const { success, next_step, errors } = await result.json();
```

### API Response Format

```json
{
    "success": true,
    "message": "Step completed successfully",
    "next_step": {
        "id": "address",
        "title": "Address Information",
        "url": "/wizard/onboarding/address"
    },
    "progress": {
        "completed": 1,
        "total": 3,
        "percentage": 33,
        "is_complete": false
    },
    "navigation": {
        "can_go_back": true,
        "can_go_forward": true,
        "previous_step": {
            "id": "personal-info",
            "title": "Personal Information"
        },
        "next_step": {
            "id": "payment",
            "title": "Payment Details"
        }
    }
}
```

---

## Advanced: Custom Wizard Class

You can extend the base wizard for custom behavior:

```php
<?php

namespace App\Wizards;

use Invelity\WizardPackage\Wizard;

class OnboardingWizard extends Wizard
{
    public function onComplete(): void
    {
        // Custom logic when wizard completes
        $user = auth()->user();
        $user->update(['onboarding_completed' => true]);
        
        // Send welcome email
        Mail::to($user)->send(new WelcomeEmail());
    }
    
    public function onStepComplete(string $stepId): void
    {
        // Custom logic after each step
        activity()
            ->causedBy(auth()->user())
            ->log("Completed step: {$stepId}");
    }
}
```

---

## Next Steps

- [View API Reference](api-reference)
- [See Real Examples](examples)
- [Learn Testing](testing)
