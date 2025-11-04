# Laravel Multi-Step Wizard Package (Headless)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/invelity/laravel-headless-wizard.svg?style=flat-square)](https://packagist.org/packages/invelity/laravel-headless-wizard)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/invelity/laravel-headless-wizard/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/invelity/laravel-headless-wizard/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/invelity/laravel-headless-wizard/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/invelity/laravel-headless-wizard/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/invelity/laravel-headless-wizard.svg?style=flat-square)](https://packagist.org/packages/invelity/laravel-headless-wizard)

A powerful **headless** multi-step wizard package for Laravel applications. Build complex, multi-page forms with progress tracking, navigation, validation, and conditional steps. **Bring your own frontend** - works with React, Vue, Inertia, Livewire, Alpine.js, or any JavaScript framework.

## 🎯 Why This Package?

- **🚀 Zero Frontend Lock-in**: Pure JSON API - use any frontend framework
- **⚡ Interactive Generators**: Beautiful CLI commands with Laravel Prompts
- **✅ Laravel-Native Validation**: Uses FormRequest classes, not custom rules
- **💾 Flexible Storage**: Session, database, or cache - your choice
- **🎨 Clean Facade API**: Intuitive, fluent, discoverable methods
- **📊 Smart Progress Tracking**: Real-time completion percentages
- **🔀 Conditional Logic**: Optional steps, dynamic flows, dependencies
- **🔔 Event-Driven**: Hook into every wizard lifecycle event
- **✨ Modern PHP 8.4**: Property hooks, readonly classes, strict types
- **🌍 Translatable**: Built-in i18n support for all messages

## Requirements

- PHP 8.4 or higher
- Laravel 11.0 or 12.0

## Installation

### 1. Install via Composer

```bash
composer require invelity/laravel-headless-wizard
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag="wizard-config"
```

This creates `config/wizard.php` where you can configure storage, routes, and behavior.

### 3. Publish Migrations (Optional)

If you want to use database storage instead of session:

```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

### 4. Publish Translations (Optional)

Customize messages in your language:

```bash
php artisan vendor:publish --tag="wizard-translations"
```

Translation files will be published to `lang/vendor/wizard/`.

## Quick Start (5 Minutes)

### Step 1: Generate a Wizard

```bash
php artisan wizard:make
```

**Interactive CLI prompts:**

```
✔ What is the wizard name? › Onboarding
✔ Must be PascalCase (e.g., Onboarding, Registration)

✓ Wizard class created: app/Wizards/Onboarding.php
✓ Registered in config: config/wizard.php
✓ Config cache cleared

Next steps:
  • Generate first step: php artisan wizard:make-step --wizard=onboarding
  • View wizard config: config/wizard.php
```

### Step 2: Generate Steps

```bash
php artisan wizard:make-step
```

**Interactive prompts:**

```
✔ Which wizard should this step belong to? › onboarding
✔ What is the step name? › PersonalInfo
✔ What is the step title? › Personal Information
✔ What is the step order? › 1
✔ Is this step optional? › No

✓ Step class created: app/Wizards/Steps/PersonalInfoStep.php
✓ FormRequest created: app/Http/Requests/Wizards/PersonalInfoRequest.php
✓ Registered in wizard: onboarding
✓ Config cache cleared

Next steps:
  • Add validation rules: app/Http/Requests/Wizards/PersonalInfoRequest.php
  • Implement business logic: app/Wizards/Steps/PersonalInfoStep.php
  • Generate another step: php artisan wizard:make-step --wizard=onboarding
```

**Generate multiple steps:**

```bash
php artisan wizard:make-step --wizard=onboarding  # Preferences
php artisan wizard:make-step --wizard=onboarding  # Review
```

### Step 3: Add Validation Rules

Edit `app/Http/Requests/Wizards/PersonalInfoRequest.php`:

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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
```

### Step 4: Implement Business Logic (Optional)

Edit `app/Wizards/Steps/PersonalInfoStep.php`:

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
            isOptional: false,
            canSkip: false,
        );
    }

    public function process(StepData $data): StepResult
    {
        // Data is already validated by PersonalInfoRequest
        
        // Optional: Transform or process data
        $processedData = [
            'full_name' => $data->get('first_name') . ' ' . $data->get('last_name'),
            'email' => strtolower($data->get('email')),
            'phone' => $data->get('phone'),
            'processed_at' => now()->toIso8601String(),
        ];
        
        // Optional: Perform side effects
        // Log::info('User registered', $processedData);
        // Cache::put("pending-user:{$data->get('email')}", $processedData, 3600);
        
        return StepResult::success(
            data: $processedData,
            message: 'Personal information saved successfully'
        );
    }
}
```

### Step 5: Build Your Frontend

The package provides JSON API endpoints. Build your UI with any framework:

**Available Routes:**

```
GET  /wizard/{wizard}/{step}              - Show step
POST /wizard/{wizard}/{step}              - Process step
POST /wizard/{wizard}/{step}/skip         - Skip optional step
POST /wizard/{wizard}/complete            - Complete wizard
GET  /wizard/{wizard}/{id}/edit/{step}    - Edit mode
PUT  /wizard/{wizard}/{id}/edit/{step}    - Update step
DELETE /wizard/{wizard}/{id}              - Delete wizard
```

## Frontend Integration

### React + TypeScript

```typescript
// hooks/useWizard.ts
import { useState, useEffect } from 'react';

interface WizardState {
  wizard_id: string;
  current_step: string;
  completed_steps: string[];
  progress: {
    total_steps: number;
    completed_steps: number;
    completion_percentage: number;
    is_complete: boolean;
  };
  navigation: Array<{
    id: string;
    title: string;
    status: 'completed' | 'current' | 'incomplete';
    label: string;
    icon: string;
  }>;
}

export function useWizard(wizardId: string) {
  const [state, setState] = useState<WizardState | null>(null);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const fetchStep = async (stepId: string) => {
    const res = await fetch(`/wizard/${wizardId}/${stepId}`);
    const data = await res.json();
    if (data.success) {
      setState(data.data);
    }
  };

  const processStep = async (stepId: string, formData: object) => {
    setLoading(true);
    setErrors({});
    
    const res = await fetch(`/wizard/${wizardId}/${stepId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(formData),
    });
    
    const data = await res.json();
    
    if (data.success) {
      setState(data.data);
      return { success: true, nextStep: data.data.next_step };
    } else {
      setErrors(data.errors || {});
      return { success: false, errors: data.errors };
    }
    
    setLoading(false);
  };

  return { state, loading, errors, processStep, fetchStep };
}

// components/Wizard.tsx
import { useWizard } from '../hooks/useWizard';

export function Wizard({ wizardId }: { wizardId: string }) {
  const { state, loading, errors, processStep, fetchStep } = useWizard(wizardId);
  const [formData, setFormData] = useState({});

  useEffect(() => {
    if (state?.current_step) {
      fetchStep(state.current_step);
    }
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const result = await processStep(state!.current_step, formData);
    
    if (result.success && result.nextStep) {
      fetchStep(result.nextStep);
    }
  };

  if (!state) return <div>Loading...</div>;

  return (
    <div className="wizard">
      {/* Progress Bar */}
      <div className="progress-bar">
        <div 
          className="progress-fill" 
          style={{ width: `${state.progress.completion_percentage}%` }}
        />
        <span>{state.progress.completion_percentage}% Complete</span>
      </div>

      {/* Navigation Breadcrumbs */}
      <nav className="wizard-nav">
        {state.navigation.map((item) => (
          <div 
            key={item.id} 
            className={`nav-item ${item.status}`}
          >
            <span className="icon">{item.icon}</span>
            <span className="label">{item.label}</span>
          </div>
        ))}
      </nav>

      {/* Step Form */}
      <form onSubmit={handleSubmit}>
        <h2>{state.navigation.find(n => n.id === state.current_step)?.title}</h2>
        
        {/* Render fields based on current step */}
        {errors && Object.keys(errors).length > 0 && (
          <div className="errors">
            {Object.entries(errors).map(([field, messages]) => (
              <div key={field}>{messages.join(', ')}</div>
            ))}
          </div>
        )}
        
        <button type="submit" disabled={loading}>
          {loading ? 'Processing...' : 'Next'}
        </button>
      </form>
    </div>
  );
}
```

### Vue 3 + Composition API

```vue
<!-- composables/useWizard.ts -->
<script setup lang="ts">
import { ref, onMounted } from 'vue';

export function useWizard(wizardId: string) {
  const state = ref(null);
  const loading = ref(false);
  const errors = ref({});

  const fetchStep = async (stepId: string) => {
    const res = await fetch(`/wizard/${wizardId}/${stepId}`);
    const data = await res.json();
    if (data.success) {
      state.value = data.data;
    }
  };

  const processStep = async (stepId: string, formData: object) => {
    loading.value = true;
    errors.value = {};
    
    const res = await fetch(`/wizard/${wizardId}/${stepId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData),
    });
    
    const data = await res.json();
    
    if (data.success) {
      state.value = data.data;
      loading.value = false;
      return { success: true, nextStep: data.data.next_step };
    } else {
      errors.value = data.errors;
      loading.value = false;
      return { success: false };
    }
  };

  return { state, loading, errors, processStep, fetchStep };
}
</script>

<!-- components/Wizard.vue -->
<template>
  <div v-if="state" class="wizard">
    <!-- Progress -->
    <div class="progress-bar">
      <div 
        class="progress-fill" 
        :style="{ width: state.progress.completion_percentage + '%' }"
      ></div>
    </div>

    <!-- Navigation -->
    <nav class="wizard-nav">
      <div 
        v-for="item in state.navigation" 
        :key="item.id"
        :class="['nav-item', item.status]"
      >
        <span>{{ item.label }}</span>
      </div>
    </nav>

    <!-- Form -->
    <form @submit.prevent="handleSubmit">
      <slot :step="state.current_step" :errors="errors"></slot>
      <button type="submit" :disabled="loading">Next</button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { useWizard } from '../composables/useWizard';

const props = defineProps<{ wizardId: string }>();
const { state, loading, errors, processStep, fetchStep } = useWizard(props.wizardId);

onMounted(() => {
  fetchStep(state.value?.current_step || 'personal-info');
});
</script>
```

### Inertia.js + React

```typescript
// Pages/Wizard/Show.tsx
import { useForm } from '@inertiajs/react';

export default function WizardShow({ wizard, step, navigation, progress }) {
  const { data, setData, post, processing, errors } = useForm({
    first_name: '',
    last_name: '',
    email: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('wizard.store', { wizard: wizard.id, step: step.id }));
  };

  return (
    <div>
      <ProgressBar percentage={progress.completion_percentage} />
      <Navigation items={navigation} />
      
      <form onSubmit={handleSubmit}>
        <input 
          value={data.first_name} 
          onChange={e => setData('first_name', e.target.value)}
        />
        {errors.first_name && <span>{errors.first_name}</span>}
        
        <button disabled={processing}>Next</button>
      </form>
    </div>
  );
}
```

### Livewire

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Invelity\WizardPackage\Facades\Wizard;

class WizardForm extends Component
{
    public string $wizardId = 'onboarding';
    public array $formData = [];
    public array $errors = [];

    public function mount()
    {
        Wizard::initialize($this->wizardId);
    }

    public function submit()
    {
        $currentStep = Wizard::getCurrentStep();
        
        $result = Wizard::processStep($currentStep->getId(), $this->formData);
        
        if ($result->success) {
            $this->formData = [];
            $this->errors = [];
        } else {
            $this->errors = $result->errors;
        }
    }

    public function render()
    {
        return view('livewire.wizard-form', [
            'currentStep' => Wizard::getCurrentStep(),
            'progress' => Wizard::getProgress(),
            'navigation' => Wizard::getNavigation(),
        ]);
    }
}
```

### Alpine.js + Blade

```blade
<div x-data="wizardData('onboarding')" x-init="initialize()">
    <!-- Progress Bar -->
    <div class="progress-bar">
        <div 
            :style="`width: ${state?.progress?.completion_percentage || 0}%`"
            class="progress-fill"
        ></div>
        <span x-text="`${state?.progress?.completion_percentage || 0}% Complete`"></span>
    </div>

    <!-- Navigation -->
    <nav class="wizard-nav">
        <template x-for="item in state?.navigation || []" :key="item.id">
            <div :class="`nav-item ${item.status}`">
                <span x-text="item.label"></span>
            </div>
        </template>
    </nav>

    <!-- Form -->
    <form @submit.prevent="submitStep()">
        <template x-if="errors && Object.keys(errors).length > 0">
            <div class="alert alert-error">
                <template x-for="(messages, field) in errors">
                    <div x-text="messages.join(', ')"></div>
                </template>
            </div>
        </template>

        <input 
            type="text" 
            x-model="formData.first_name" 
            placeholder="First Name"
        />
        <input 
            type="email" 
            x-model="formData.email" 
            placeholder="Email"
        />
        
        <button type="submit" :disabled="loading">
            <span x-show="!loading">Next</span>
            <span x-show="loading">Processing...</span>
        </button>
    </form>
</div>

<script>
function wizardData(wizardId) {
    return {
        state: null,
        formData: {},
        errors: {},
        loading: false,
        
        async initialize() {
            const res = await fetch(`/wizard/${wizardId}/${this.getCurrentStep()}`);
            const data = await res.json();
            if (data.success) {
                this.state = data.data;
            }
        },
        
        getCurrentStep() {
            return 'personal-info'; // Or get from URL
        },
        
        async submitStep() {
            this.loading = true;
            this.errors = {};
            
            const res = await fetch(`/wizard/${wizardId}/${this.state.current_step}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.formData),
            });
            
            const data = await res.json();
            
            if (data.success) {
                this.state = data.data;
                this.formData = {};
                
                if (data.data.next_step) {
                    window.location.href = `/wizard/${wizardId}/${data.data.next_step}`;
                }
            } else {
                this.errors = data.errors || {};
            }
            
            this.loading = false;
        },
    };
}
</script>
```

## Using the Facade API

For backend usage, the `Wizard` facade provides a clean, fluent API:

```php
<?php

use Invelity\WizardPackage\Facades\Wizard;

// Initialize wizard
Wizard::initialize('onboarding');

// Get current step
$step = Wizard::getCurrentStep();
echo $step->getTitle(); // "Personal Information"
echo $step->getId();    // "personal-info"
echo $step->getOrder(); // 1

// Process step (validation happens via FormRequest)
$result = Wizard::processStep('personal-info', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);

if ($result->success) {
    echo $result->message; // "Step completed successfully"
}

// Get all collected data
$allData = Wizard::getAllData();
// [
//     'personal-info' => ['first_name' => 'John', ...],
//     'preferences' => ['theme' => 'dark', ...],
// ]

// Get specific step data
$personalInfo = Wizard::getStepData('personal-info');

// Navigation with status
$navigation = Wizard::getNavigation();
foreach ($navigation as $item) {
    echo $item->label;  // "1. Personal Information"
    echo $item->icon;   // "check" (completed), "arrow-right" (current), "circle" (incomplete)
    echo $item->status; // "completed", "current", "incomplete"
}

// Progress tracking
$progress = Wizard::getProgress();
echo $progress->completionPercentage; // 33
echo $progress->totalSteps;           // 3
echo $progress->completedSteps;       // 1
echo $progress->isComplete;           // false

// Check step access
if (Wizard::canAccessStep('review')) {
    // User can access this step
}

// Check if step is completed
if (Wizard::isStepCompleted('personal-info')) {
    // Step is done
}

// Get completed steps
$completed = Wizard::getCompletedSteps();
// ['personal-info', 'preferences']

// Skip optional step
Wizard::skipStep('newsletter');

// Navigate to specific step
Wizard::navigateToStep('review');

// Complete wizard
$result = Wizard::complete();
if ($result->success) {
    // Wizard completed successfully
    $allData = $result->data;
}

// Reset wizard (start over)
Wizard::reset();

// Load wizard from database for editing
Wizard::loadFromStorage('onboarding', $instanceId);

// Delete wizard instance
Wizard::deleteWizard('onboarding', $instanceId);
```

### Available Facade Methods

**Wizard Lifecycle:**
- `initialize(string $wizardId, array $config = []): void`
- `reset(): void`
- `complete(): StepResult`
- `isComplete(): bool`

**Step Management:**
- `getCurrentStep(): ?WizardStepInterface`
- `getStep(string $stepId): WizardStepInterface`
- `processStep(string $stepId, array $data): StepResult`
- `skipStep(string $stepId): void`

**Navigation:**
- `canAccessStep(string $stepId): bool`
- `navigateToStep(string $stepId): void`
- `getNavigation(): array<NavigationItem>`
- `getNextStep(): ?WizardStepInterface`
- `getPreviousStep(): ?WizardStepInterface`

**Data Access:**
- `getAllData(): array`
- `getStepData(string $stepId): ?array`
- `isStepCompleted(string $stepId): bool`
- `getCompletedSteps(): array`

**Progress:**
- `getProgress(): WizardProgressValue`

**Database Operations:**
- `loadFromStorage(string $wizardId, int $instanceId): void`
- `deleteWizard(string $wizardId, int $instanceId): void`

## API Response Formats

### Success Response

```json
{
  "success": true,
  "data": {
    "wizard_id": "onboarding",
    "current_step": "personal-info",
    "next_step": "preferences",
    "completed_steps": ["personal-info"],
    "progress": {
      "total_steps": 3,
      "completed_steps": 1,
      "current_step_position": 2,
      "completion_percentage": 33,
      "is_complete": false,
      "remaining_steps": ["preferences", "review"]
    },
    "navigation": [
      {
        "id": "personal-info",
        "title": "Personal Information",
        "order": 1,
        "status": "completed",
        "label": "1. Personal Information",
        "icon": "check"
      },
      {
        "id": "preferences",
        "title": "Preferences",
        "order": 2,
        "status": "current",
        "label": "2. Preferences",
        "icon": "arrow-right"
      },
      {
        "id": "review",
        "title": "Review",
        "order": 3,
        "status": "incomplete",
        "label": "3. Review",
        "icon": "circle"
      }
    ],
    "step_data": {
      "personal-info": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com"
      }
    }
  },
  "message": "Step completed successfully"
}
```

### Validation Error Response

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "phone": [
      "Please enter a valid phone number."
    ]
  }
}
```

### Wizard Completion Response

```json
{
  "success": true,
  "data": {
    "personal-info": {...},
    "preferences": {...},
    "review": {...}
  },
  "message": "Wizard completed successfully"
}
```

## Advanced Features

### Optional Steps

```php
class NewsletterStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'newsletter',
            title: 'Newsletter Subscription',
            order: 4,
            isOptional: true,  // Step can be skipped
            canSkip: true      // Show skip button
        );
    }
}
```

**Frontend Usage:**

```typescript
// Skip optional step
await fetch(`/wizard/onboarding/newsletter/skip`, { method: 'POST' });
```

### Conditional Steps

Show/hide steps based on previous data:

```php
class BillingStep extends AbstractStep
{
    public function shouldSkip(array $wizardData): bool
    {
        // Skip billing if user selected "free" plan
        return ($wizardData['plan-selection']['plan'] ?? null) === 'free';
    }
}

class CompanyInfoStep extends AbstractStep
{
    public function shouldShow(array $wizardData): bool
    {
        // Only show if account type is "business"
        return ($wizardData['personal-info']['account_type'] ?? null) === 'business';
    }
}
```

### Step Dependencies

Ensure required steps are completed first:

```php
class ReviewStep extends AbstractStep
{
    public function getDependencies(): array
    {
        return ['personal-info', 'plan-selection', 'billing'];
    }
}
```

### Complex Data Processing

```php
class PersonalInfoStep extends AbstractStep
{
    public function __construct(
        private readonly UserService $userService,
        private readonly NotificationService $notifications,
    ) {
        parent::__construct(
            id: 'personal-info',
            title: 'Personal Information',
            order: 1,
        );
    }

    public function beforeProcess(StepData $data): void
    {
        // Runs before validation
        Log::info('Starting personal info step', ['email' => $data->get('email')]);
    }

    public function process(StepData $data): StepResult
    {
        // Main business logic
        try {
            $user = $this->userService->createPendingUser([
                'name' => $data->get('first_name') . ' ' . $data->get('last_name'),
                'email' => $data->get('email'),
            ]);

            return StepResult::success(
                data: ['user_id' => $user->id],
                message: 'Personal information saved'
            );
        } catch (\Exception $e) {
            return StepResult::failure([
                'email' => ['Failed to create user: ' . $e->getMessage()]
            ]);
        }
    }

    public function afterProcess(StepResult $result): void
    {
        // Runs after successful processing
        if ($result->success) {
            $this->notifications->send('Personal info step completed');
        }
    }
}
```

### Storage Backends

**Session Storage (Default):**

```php
// config/wizard.php
'storage' => [
    'driver' => 'session',
],
```

**Database Storage:**

```php
'storage' => [
    'driver' => 'database',
],

'database' => [
    'table' => 'wizard_progress',
    'connection' => null, // Use default connection
],
```

**Cache Storage:**

```php
'storage' => [
    'driver' => 'cache',
    'ttl' => 7200, // 2 hours
],

'cache' => [
    'driver' => 'redis',
    'ttl' => 7200,
],
```

### Events

Listen to wizard lifecycle events:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Invelity\WizardPackage\Events\{
    WizardStarted,
    StepCompleted,
    StepSkipped,
    WizardCompleted
};

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WizardStarted::class => [
            LogWizardStart::class,
        ],
        StepCompleted::class => [
            UpdateUserProgress::class,
            SendStepCompletionEmail::class,
        ],
        StepSkipped::class => [
            LogSkippedStep::class,
        ],
        WizardCompleted::class => [
            ProcessWizardData::class,
            CreateUserAccount::class,
            SendWelcomeEmail::class,
        ],
    ];
}
```

**Event Properties:**

```php
// WizardStarted
$event->wizardId;
$event->userId;
$event->sessionId;
$event->initialData;

// StepCompleted
$event->wizardId;
$event->stepId;
$event->data;
$event->percentComplete;

// StepSkipped
$event->wizardId;
$event->stepId;
$event->sessionId;

// WizardCompleted
$event->wizardId;
$event->data; // All collected data
$event->completedAt;
```

### Edit Mode (CRUD Operations)

Load existing wizard data for editing:

```php
// Controller
public function edit(int $wizardInstanceId)
{
    Wizard::loadFromStorage('onboarding', $wizardInstanceId);
    
    $data = Wizard::getAllData();
    
    return response()->json([
        'data' => $data,
        'current_step' => Wizard::getCurrentStep(),
    ]);
}

// Update specific step
public function updateStep(int $wizardInstanceId, string $stepId, Request $request)
{
    Wizard::loadFromStorage('onboarding', $wizardInstanceId);
    
    $result = Wizard::processStep($stepId, $request->all());
    
    return response()->json($result);
}

// Delete wizard
public function destroy(int $wizardInstanceId)
{
    Wizard::deleteWizard('onboarding', $wizardInstanceId);
    
    return response()->json(['message' => 'Wizard deleted']);
}
```

## Configuration

Full configuration reference in `config/wizard.php`:

```php
return [
    'storage' => [
        'driver' => env('WIZARD_STORAGE', 'session'),
        'ttl' => 3600,
    ],

    'wizards' => [
        'onboarding' => [
            'class' => App\Wizards\Onboarding::class,
            'steps' => [
                App\Wizards\Steps\PersonalInfoStep::class,
                App\Wizards\Steps\PreferencesStep::class,
                App\Wizards\Steps\ReviewStep::class,
            ],
        ],
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => env('WIZARD_ROUTE_PREFIX', 'wizard'),
        'middleware' => ['web', 'wizard.session'],
    ],

    'navigation' => [
        'allow_jump' => false,      // Allow direct navigation to any step
        'show_all_steps' => true,   // Show all steps in breadcrumbs
        'mark_completed' => true,   // Mark completed steps visually
    ],

    'validation' => [
        'validate_on_navigate' => true,
        'allow_skip_optional' => true,
    ],

    'events' => [
        'dispatch' => true,
        'log_progress' => false,
    ],

    'cleanup' => [
        'abandoned_after_days' => 30,
        'auto_cleanup' => false,
    ],
];
```

## Internationalization

All messages are translatable. Publish translations:

```bash
php artisan vendor:publish --tag="wizard-translations"
```

Available languages:
- English (`lang/vendor/wizard/en.json`)
- Slovak (`lang/vendor/wizard/sk.json`)

Add your own language:

```bash
cp lang/vendor/wizard/en.json lang/vendor/wizard/es.json
```

**Translatable messages:**
- Error messages
- Validation messages
- Success messages
- Command output

## PHP 8.4 Features

This package leverages modern PHP 8.4 features:

### Property Hooks

Computed properties using property hooks:

```php
$result = Wizard::processStep('step-id', $data);

// Computed via property hook (no method call)
echo $result->isSuccess;   // true/false
echo $result->hasErrors;   // true/false

$progress = Wizard::getProgress();
echo $progress->completionPercentage;  // 33

$navigation = Wizard::getNavigation();
foreach ($navigation as $item) {
    echo $item->label;  // "1. Personal Info" (computed)
    echo $item->icon;   // "check" (computed based on status)
}
```

### Readonly Classes

All Actions, Middleware, and Value Objects are `final readonly class` for immutability:

```php
final readonly class CompleteWizardAction
{
    public function __construct(
        private WizardManagerInterface $manager,
    ) {}
}
```

### Modern Array Functions

Uses `array_find()` and `array_any()`:

```php
$step = array_find(
    $steps, 
    fn($s) => $s->getId() === 'personal-info'
);

$hasCompleted = array_any(
    $completedSteps, 
    fn($id) => $id === 'review'
);
```

## Artisan Commands

### Generate Wizard

```bash
# Interactive
php artisan wizard:make

# With name
php artisan wizard:make Onboarding

# Force overwrite
php artisan wizard:make Onboarding --force
```

### Generate Step

```bash
# Interactive
php artisan wizard:make-step

# With options
php artisan wizard:make-step PersonalInfo \
    --wizard=onboarding \
    --order=1 \
    --optional=false

# Force overwrite
php artisan wizard:make-step PersonalInfo --wizard=onboarding --force
```

Both commands:
- ✅ Auto-register in `config/wizard.php`
- ✅ Create directory structure
- ✅ Generate boilerplate code
- ✅ Clear config cache
- ✅ Show next steps

## Testing

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --filter=WizardManagerTest

# Static analysis
composer analyse

# Code style check
composer format

# Run all quality checks
composer test && composer analyse && composer format
```

**Package includes 131 tests:**
- Unit tests (core functionality)
- Integration tests (full wizard flows)
- Feature tests (commands, validation)
- Architecture tests (SOLID principles, PHP 8.4 compliance)

## Troubleshooting

### Session Not Working

Make sure session middleware is registered:

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Illuminate\Session\Middleware\StartSession::class,
    ]);
})

// Or in routes/web.php
Route::middleware(['web'])->group(function () {
    // Your routes
});
```

### Steps Not Auto-Registered

Clear config cache:

```bash
php artisan config:clear
```

### Validation Not Working

Make sure FormRequest returns validation rules:

```php
public function rules(): array
{
    return [
        'email' => 'required|email',
    ];
}
```

### Database Storage Issues

Run migrations:

```bash
php artisan migrate
```

Check config:

```php
'storage' => [
    'driver' => 'database',
],
```

## Security

- Never commit sensitive data in wizard steps
- Always validate user input via FormRequest classes
- Use proper authentication middleware
- Sanitize file uploads
- Implement rate limiting on wizard endpoints

Report security vulnerabilities via [GitHub Security](../../security/policy).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md).

Please ensure:
- Tests pass (`composer test`)
- PHPStan passes (`composer analyse`)
- Code style passes (`composer format`)
- Follow SOLID principles
- Use PHP 8.4 features where appropriate

## Credits

- [Martin Halaj](https://github.com/Martin-1182)
- [All Contributors](../../contributors)

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
