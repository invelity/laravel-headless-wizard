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

**Interactive prompts:**
```
 What type of wizard do you want to create?
  [blade] Blade (Traditional server-side rendering)
  [api] API (Headless JSON responses)
  [livewire] Livewire (Reactive components)
  [inertia] Inertia.js (SPA with Vue/React)
 > blade

ℹ Wizard created successfully!
✎ Wizard class: app/Wizards/OnboardingWizard/Onboarding.php
✎ Controller: app/Http/Controllers/OnboardingController.php
✎ Views: resources/views/wizards/onboarding/
```

Or use command options to skip interactive prompts:

```bash
php artisan wizard:make Onboarding --type=api
```

### 2. Generate Steps

```bash
php artisan wizard:make-step Onboarding PersonalInfo --order=1
```

**Interactive prompts:**
```
 What is the step title? › Personal Information
 Is this step optional? › No

ℹ Step created successfully!
✎ Step class: app/Wizards/OnboardingWizard/Steps/PersonalInfoStep.php
✎ FormRequest: app/Http/Requests/Wizards/PersonalInfoRequest.php
✎ Step will be auto-discovered

✎ Next steps:
  • Add validation rules: app/Http/Requests/Wizards/PersonalInfoRequest.php
  • Implement business logic: app/Wizards/OnboardingWizard/Steps/PersonalInfoStep.php
```

---

## Wizard Types

Laravel Headless Wizard supports 4 wizard types to fit your stack:

### Blade Wizards

Traditional server-side rendered wizards with Blade templates and pre-built components.

```bash
php artisan wizard:make Onboarding --type=blade
```

**Best for:**
- Traditional Laravel applications
- Server-side rendering
- Rapid prototyping with pre-built components

**Features:**
- Auto-generated Blade views with layout
- Pre-built components (ProgressBar, Navigation, FormWrapper)
- CSRF protection included
- Traditional form submissions

### API Wizards

Headless JSON API for modern SPA frameworks (React, Vue, Angular, Svelte).

```bash
php artisan wizard:make Onboarding --type=api
```

**Best for:**
- Decoupled frontend/backend
- Mobile apps
- Multiple frontend consumers

**Features:**
- Pure JSON responses
- RESTful API endpoints
- useWizard() Vue composable included
- Requires CSRF exception setup

**CSRF Setup Required:**
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'wizard/onboarding/*',
    ]);
})
```

### Livewire Wizards

Reactive components with Laravel Livewire.

```bash
php artisan wizard:make Onboarding --type=livewire
```

**Best for:**
- Reactive UIs without JavaScript frameworks
- Real-time validation
- Dynamic forms

### Inertia Wizards

SPA experience with Vue/React using Inertia.js.

```bash
php artisan wizard:make Onboarding --type=inertia
```

**Best for:**
- Modern SPA with server-side routing
- Vue/React with Laravel backend
- Best of both worlds (SPA + Laravel)

---

## Wizard Structure

A wizard consists of:

1. **Wizard Class** - Orchestrates the overall flow (`app/Wizards/{Name}Wizard/{Name}.php`)
2. **Step Classes** - Individual wizard steps (`app/Wizards/{Name}Wizard/Steps/`)
3. **Form Requests** - Laravel validation for each step (`app/Http/Requests/Wizards/`)
4. **Auto-Discovery** - Wizards are automatically discovered, no config registration needed

---

## Creating Custom Steps

### Step Class Example

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

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
            order: 1
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PersonalInfoRequest::class;
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

Make a step optional by passing `isOptional: true`:

```php
public function __construct()
{
    parent::__construct(
        id: 'newsletter',
        title: 'Newsletter Preferences',
        order: 3,
        isOptional: true, // Users can skip this step
        canSkip: true
    );
}

public function getFormRequest(): ?string
{
    return null; // No validation for optional step
}
```

{: .note }
> **Smart Defaults**: Generated step constructors automatically omit `isOptional: false` and `canSkip: false` for cleaner code. Only include these parameters when set to `true`.

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

## Blade Components

For Blade wizards, use pre-built components for rapid development:

### Layout Component

Provides base wizard layout with title and content slot:

```blade
<x-wizard::layout title="User Onboarding">
    <!-- Your wizard content here -->
</x-wizard::layout>
```

### Progress Bar Component

Shows wizard completion progress:

```blade
<x-wizard::progress-bar 
    :steps="$steps" 
    :currentStep="$currentStep" 
/>
```

The component automatically calculates completion percentage based on current step position.

### Form Wrapper Component

Wraps your form with CSRF protection and error handling:

```blade
<x-wizard::form-wrapper :action="route('wizard.onboarding.store', $step->id)">
    <!-- Your form fields -->
    <input type="text" name="name" value="{{ old('name') }}" />
    <input type="email" name="email" value="{{ old('email') }}" />
    
    <!-- Navigation buttons -->
</x-wizard::form-wrapper>
```

Automatically displays validation errors at the top of the form.

### Step Navigation Component

Provides back/next/complete buttons:

```blade
<x-wizard::step-navigation 
    :canGoBack="$canGoBack"
    :canGoForward="$canGoForward"
    :isLastStep="$isLastStep"
    :previousStep="$previousStep ?? null"
    :nextStep="$nextStep ?? null"
    backText="Previous"
    nextText="Next"
    completeText="Complete"
/>
```

### Complete Example

```blade
<x-wizard::layout title="User Onboarding">
    <x-wizard::progress-bar :steps="$steps" :currentStep="$currentStep" />
    
    <x-wizard::form-wrapper :action="route('wizard.onboarding.store', 'personal-info')">
        <h2>Personal Information</h2>
        
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" />
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" />
        </div>
        
        <x-wizard::step-navigation 
            :canGoBack="false"
            :canGoForward="true"
            :isLastStep="false"
            :nextStep="$nextStep"
        />
    </x-wizard::form-wrapper>
</x-wizard::layout>
```

**Customization:**

Publish components to customize styling:

```bash
php artisan vendor:publish --tag="wizard-components"
```

Components will be available in `resources/views/vendor/wizard-package/components/`.

---

## Vue 3 Composable

For API/SPA wizards, use the `useWizard()` composable:

### Installation

Publish assets:

```bash
php artisan vendor:publish --tag="wizard-assets"
```

Import in your Vue component:

```typescript
import { useWizard } from '@/composables/useWizard';

export default {
    setup() {
        const { 
            state, 
            currentStep, 
            canGoBack, 
            canGoForward, 
            isLastStep,
            initialize, 
            submitStep, 
            goToStep 
        } = useWizard('onboarding');
        
        return { 
            state, 
            currentStep, 
            canGoBack, 
            canGoForward, 
            isLastStep,
            initialize, 
            submitStep, 
            goToStep 
        };
    }
};
```

### Reactive State

```typescript
interface WizardState {
    currentStepIndex: number;
    steps: WizardStep[];
    formData: Record<string, any>;
    errors: Record<string, string[]>;
    loading: boolean;
    completed: boolean;
    wizardData: any;
}
```

### Methods

#### Initialize Wizard

```typescript
await initialize();
```

#### Submit Step

```typescript
const result = await submitStep({
    name: 'John Doe',
    email: 'john@example.com'
});

if (result.success) {
    console.log('Step completed!', result.nextStep);
} else {
    console.error('Validation errors:', result.errors);
}
```

#### Navigate to Step

```typescript
await goToStep('personal-info');
```

#### Form Helpers

```typescript
// Set field value
setFieldValue('email', 'john@example.com');

// Get field error
const emailError = getFieldError('email');

// Clear all errors
clearErrors();
```

### Complete Vue Example

```vue
<template>
    <div v-if="!state.loading" class="wizard">
        <div class="progress">
            Step {{ state.currentStepIndex + 1 }} of {{ state.steps.length }}
            <progress :value="state.currentStepIndex + 1" :max="state.steps.length"></progress>
        </div>
        
        <h2>{{ currentStep?.title }}</h2>
        
        <form @submit.prevent="handleSubmit">
            <div v-if="currentStep?.id === 'personal-info'">
                <input v-model="formData.name" type="text" placeholder="Name" />
                <span v-if="getFieldError('name')" class="error">
                    {{ getFieldError('name') }}
                </span>
                
                <input v-model="formData.email" type="email" placeholder="Email" />
                <span v-if="getFieldError('email')" class="error">
                    {{ getFieldError('email') }}
                </span>
            </div>
            
            <div class="navigation">
                <button v-if="canGoBack" type="button" @click="goToStep(previousStep.id)">
                    Previous
                </button>
                <button type="submit" :disabled="state.loading">
                    {{ isLastStep ? 'Complete' : 'Next' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useWizard } from '@/composables/useWizard';

const { 
    state, 
    currentStep, 
    canGoBack, 
    isLastStep,
    initialize, 
    submitStep, 
    goToStep,
    setFieldValue,
    getFieldError
} = useWizard('onboarding');

const formData = ref<Record<string, any>>({});

onMounted(async () => {
    await initialize();
});

const handleSubmit = async () => {
    const result = await submitStep(formData.value);
    if (result.success) {
        formData.value = {};
    }
};
</script>
```

---

## Automatic Step Reordering

When you add a new step with a specific order, existing steps are automatically reordered:

```bash
# Existing steps: Step1 (order: 1), Step3 (order: 2)
php artisan wizard:make-step Onboarding NewStep --order=2

# Result:
# - Step1 (order: 1)
# - NewStep (order: 2) ← newly inserted
# - Step3 (order: 3) ← automatically incremented
```

The package scans the `Steps/` directory and updates step order properties automatically. No manual file editing required!

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

### Vue 3 with useWizard()

**Recommended approach for SPA:**

```vue
<script setup>
import { useWizard } from '@/composables/useWizard';

const { state, currentStep, submitStep, initialize } = useWizard('onboarding');

onMounted(() => initialize());

const handleSubmit = async (formData) => {
    const result = await submitStep(formData);
    if (result.success) {
        // Navigate to next step automatically
    }
};
</script>
```

### Manual Fetch API (Alternative)

If you prefer manual control or use React/Angular:

```javascript
// Fetch wizard state
const response = await fetch('/wizard/onboarding/personal-info');
const { step, navigation, progress } = await response.json();

// Submit step data
const result = await fetch('/wizard/onboarding/personal-info', {
    method: 'POST',
    headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
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
