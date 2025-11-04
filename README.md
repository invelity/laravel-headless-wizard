# Laravel Multi-Step Wizard Package (Headless)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/websystem-studio/wizard-package.svg?style=flat-square)](https://packagist.org/packages/websystem-studio/wizard-package)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/websystem-studio/wizard-package/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/websystem-studio/wizard-package/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/websystem-studio/wizard-package/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/websystem-studio/wizard-package/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/websystem-studio/wizard-package.svg?style=flat-square)](https://packagist.org/packages/websystem-studio/wizard-package)

A powerful **headless** multi-step wizard package for Laravel applications. Build complex, multi-page forms with progress tracking, navigation, validation, and optional/conditional steps. Bring your own frontend (React, Vue, Blade, or any framework).

## ⚡ Version 2.0 - Headless Architecture

**Breaking Change Notice:** Version 2.0 is a complete architectural shift to headless/API-first design. If you're upgrading from v1.x, please see the [Migration Guide](#migrating-from-v1x).

## Features

- 🎯 **Headless Architecture** - No views included, bring your own frontend
- 🚀 **Interactive Generators** - Beautiful Laravel Prompts for wizard/step creation
- 🔄 **Auto-Registration** - Steps automatically registered in config during generation
- ✅ **FormRequest Validation** - Laravel-standard validation pattern
- 💾 **Multiple Storage Backends** - Session, database, cache
- 🎨 **Facade API** - Clean, discoverable static method interface
- 📊 **Progress Tracking** - Real-time completion percentage
- 🔀 **Optional & Conditional Steps** - Dynamic wizard flows
- 🔔 **Event System** - Hook into wizard lifecycle
- ✨ **PHP 8.4+ Features** - Property hooks, modern array functions
- 📝 **Full CRUD Support** - Create, edit, update, delete wizards

## Requirements

- PHP 8.4 or higher
- Laravel 11.0 or 12.0

## Installation

### 1. Install via Composer

```bash
composer require websystem-studio/wizard-package:^2.0
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag="wizard-config"
```

This creates `config/wizard-package.php`.

### 3. Publish and Run Migrations (Optional)

If you want to store wizard data in the database:

```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

## Quick Start Guide (10 Minutes)

### Step 1: Generate a Wizard

```bash
php artisan wizard:make
```

**Interactive prompts:**

```
✔ What is the wizard name? Onboarding

✓ Wizard class created: app/Wizards/Onboarding.php
✓ Registered in config: config/wizard-package.php
```

### Step 2: Generate Steps

```bash
php artisan wizard:make-step
```

**Interactive prompts:**

```
✔ Which wizard should this step belong to? Onboarding
✔ What is the step name? PersonalInfo
✔ What is the step title? Personal Information
✔ Step order number? 1
✔ Is this step optional? No

✓ Step class created: app/Wizards/Steps/PersonalInfoStep.php
✓ Request class created: app/Http/Requests/Wizards/PersonalInfoRequest.php
✓ Registered in wizard config
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|regex:/^\+?[1-9]\d{1,14}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
```

### Step 4: Implement Business Logic

Edit `app/Wizards/Steps/PersonalInfoStep.php`:

```php
<?php

namespace App\Wizards\Steps;

use WebSystem\WizardPackage\Steps\AbstractStep;
use WebSystem\WizardPackage\ValueObjects\StepData;
use WebSystem\WizardPackage\ValueObjects\StepResult;

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
        
        // Optional: Transform data
        $processedData = [
            'full_name' => $data->get('first_name') . ' ' . $data->get('last_name'),
            'email' => strtolower($data->get('email')),
            'phone' => $data->get('phone'),
        ];
        
        return new StepResult(
            success: true,
            data: $processedData,
            message: 'Personal information saved successfully',
        );
    }
}
```

### Step 5: Build Your Frontend

**React Example:**

```typescript
// hooks/useWizard.ts
import { useState, useEffect } from 'react';

export function useWizard(wizardId: string) {
  const [state, setState] = useState(null);
  const [loading, setLoading] = useState(false);

  const initialize = async () => {
    const res = await fetch(`/api/wizards/${wizardId}/initialize`, {
      method: 'POST',
    });
    const data = await res.json();
    if (data.success) {
      setState(data.data);
    }
  };

  const processStep = async (stepId: string, formData: object) => {
    const res = await fetch(`/api/wizards/${wizardId}/steps/${stepId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData),
    });
    const data = await res.json();
    
    if (data.success) {
      setState(prev => ({
        ...prev,
        current_step: data.data.next_step,
        progress: data.data.progress,
      }));
    }
    
    return data;
  };

  useEffect(() => {
    initialize();
  }, [wizardId]);

  return { state, loading, processStep };
}
```

**Vue Example:**

```typescript
// composables/useWizard.ts
export function useWizard(wizardId: string) {
  const state = ref(null);
  const loading = ref(false);
  
  const processStep = async (stepId: string, formData: object) => {
    loading.value = true;
    const res = await fetch(`/api/wizards/${wizardId}/steps/${stepId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData),
    });
    const data = await res.json();
    
    if (data.success) {
      state.value.current_step = data.data.next_step;
      state.value.progress = data.data.progress;
    }
    
    loading.value = false;
    return data;
  };
  
  return { state, loading, processStep };
}
```

**Blade + Alpine.js Example:**

```blade
<div x-data="wizardData('onboarding')" x-init="initialize()">
    <div class="progress-bar">
        <div :style="`width: ${state?.progress?.completion_percentage || 0}%`"></div>
    </div>

    <form @submit.prevent="submitStep('personal-info')">
        <input type="text" x-model="formData.first_name" placeholder="First Name" />
        <input type="email" x-model="formData.email" placeholder="Email" />
        <button type="submit">Next</button>
    </form>
</div>

<script>
function wizardData(wizardId) {
    return {
        state: null,
        formData: {},
        
        async initialize() {
            const res = await fetch(`/api/wizards/${wizardId}/initialize`, {
                method: 'POST',
            });
            this.state = (await res.json()).data;
        },
        
        async submitStep(stepId) {
            const res = await fetch(`/api/wizards/${wizardId}/steps/${stepId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.formData),
            });
            const data = await res.json();
            
            if (data.success) {
                this.state = data.data;
                this.formData = {};
            }
        },
    };
}
</script>
```

## Using the Facade API

The `WizardPackage` facade provides a clean interface to all wizard functionality:

```php
<?php

use WebSystem\WizardPackage\Facades\WizardPackage;

// Initialize wizard
WizardPackage::initialize('onboarding');

// Get current step
$step = WizardPackage::getCurrentStep();
echo $step->getTitle(); // "Personal Information"

// Process step (data already validated by FormRequest)
$result = WizardPackage::processStep('personal-info', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);

// Get all collected data
$allData = WizardPackage::getAllData();

// Get navigation with status
$navigation = WizardPackage::getNavigation();
foreach ($navigation as $item) {
    echo $item->label; // "1. Personal Information"
    echo $item->icon;  // "check" | "arrow-right" | "circle"
}

// Get progress
$progress = WizardPackage::getProgress();
echo $progress->completionPercentage; // 33

// Check if step is completed
if (WizardPackage::isStepCompleted('personal-info')) {
    // Step done
}

// Skip optional step
WizardPackage::skipStep('newsletter');

// Complete wizard
$result = WizardPackage::complete();

// Reset wizard
WizardPackage::reset();
```

### Available Facade Methods

**Wizard Lifecycle:**
- `initialize(string $wizardId, array $config = []): void`
- `reset(): void`
- `complete(): StepResult`

**Step Management:**
- `getCurrentStep(): ?WizardStepInterface`
- `getStep(string $stepId): WizardStepInterface`
- `processStep(string $stepId, array $data): StepResult`
- `skipStep(string $stepId): void`

**Navigation:**
- `canAccessStep(string $stepId): bool`
- `navigateToStep(string $stepId): void`
- `getNavigation(): array`

**Data Access:**
- `getAllData(): array`
- `getStepData(string $stepId): ?array`
- `isStepCompleted(string $stepId): bool`
- `getCompletedSteps(): array`

**Progress:**
- `getProgress(): WizardProgressValue`
- `isComplete(): bool`

## API Endpoints

The package provides headless JSON API endpoints:

### Initialize Wizard

```http
POST /api/wizards/{wizardId}/initialize
```

**Response:**

```json
{
  "success": true,
  "data": {
    "wizard_id": "onboarding",
    "current_step": {
      "id": "personal-info",
      "title": "Personal Information",
      "order": 1
    },
    "progress": {
      "total_steps": 3,
      "completed_steps": 0,
      "completion_percentage": 0
    },
    "navigation": [...]
  }
}
```

### Process Step

```http
POST /api/wizards/{wizardId}/steps/{stepId}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com"
}
```

**Success Response:**

```json
{
  "success": true,
  "data": {
    "step_id": "personal-info",
    "next_step": {
      "id": "preferences",
      "title": "Preferences"
    },
    "progress": {
      "completion_percentage": 33
    }
  },
  "message": "Step completed successfully"
}
```

**Validation Error Response:**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Get Wizard State

```http
GET /api/wizards/{wizardId}/state
```

### Skip Step

```http
POST /api/wizards/{wizardId}/steps/{stepId}/skip
```

### Complete Wizard

```http
POST /api/wizards/{wizardId}/complete
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
            isOptional: true,
            canSkip: true
        );
    }
}
```

### Conditional Steps

```php
class BillingStep extends AbstractStep
{
    public function shouldSkip(array $wizardData): bool
    {
        // Skip billing if user selected "free" plan
        return ($wizardData['plan-selection']['plan'] ?? null) === 'free';
    }
}
```

### Step Dependencies

```php
class ReviewStep extends AbstractStep
{
    public function getDependencies(): array
    {
        return ['personal-info', 'plan-selection'];
    }
}
```

### Storage Backends

**Session Storage (Default):**

```php
// config/wizard-package.php
'storage' => 'session',
```

**Database Storage:**

```php
'storage' => 'database',
```

**Cache Storage:**

```php
'storage' => 'cache',
```

### Events

```php
use WebSystem\WizardPackage\Events\{
    WizardStarted,
    StepCompleted,
    StepSkipped,
    WizardCompleted
};

// Listen in EventServiceProvider
protected $listen = [
    WizardCompleted::class => [
        ProcessWizardData::class,
        SendCompletionEmail::class,
    ],
];
```

## Artisan Commands

### Generate Wizard

```bash
# Interactive
php artisan wizard:make

# Non-interactive
php artisan wizard:make Onboarding
```

### Generate Step

```bash
# Interactive
php artisan wizard:make-step

# Non-interactive
php artisan wizard:make-step PersonalInfo --wizard=onboarding --order=1
```

## Configuration

```php
// config/wizard-package.php
return [
    'storage' => [
        'driver' => 'session', // session|cache|database
        'ttl' => 3600,
    ],

    'wizards' => [
        'onboarding' => [
            'class' => App\Wizards\Onboarding::class,
            'steps' => [
                App\Wizards\Steps\PersonalInfoStep::class,
                App\Wizards\Steps\PreferencesStep::class,
            ],
        ],
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'wizard',
        'middleware' => ['web'],
    ],

    'events' => [
        'dispatch' => true,
    ],
];
```

## PHP 8.4 Features

This package leverages modern PHP 8.4 features:

### Property Hooks

```php
$result = WizardPackage::processStep('step-id', $data);
echo $result->isSuccess;   // Computed via property hook
echo $result->hasErrors;   // Computed via property hook

$progress = WizardPackage::getProgress();
echo $progress->completionPercentage;  // Computed property hook

$navigation = WizardPackage::getNavigation();
foreach ($navigation as $item) {
    echo $item->label;  // "1. Personal Info" (computed)
    echo $item->icon;   // "check" (computed based on status)
}
```

### Modern Array Functions

```php
// Internally uses array_find() and array_any()
$step = WizardPackage::getStep('personal-info');
$canAccess = WizardPackage::canAccessStep('review');
```

## Migrating from v1.x

**v2.0 is a breaking release** with headless architecture. Key changes:

1. **Views Removed**: No Blade templates included
2. **Validation Moved**: From `Step::rules()` to `FormRequest` classes
3. **Controllers Return JSON**: Not views
4. **Config Structure**: Auto-registration format
5. **Commands Split**: Separate `wizard:make` and `wizard:make-step`

**Migration Steps:**

```bash
# 1. Update composer
composer require websystem-studio/wizard-package:^2.0

# 2. Remove old views
rm -rf resources/views/wizards

# 3. Create FormRequests for each step
php artisan wizard:make-step --wizard=onboarding

# 4. Move validation rules from Step to FormRequest

# 5. Build your frontend UI
```

See full migration guide in [docs/migration-v1-to-v2.md](docs/migration-v1-to-v2.md).

## Testing

```bash
# Run all tests
composer test

# Static analysis
composer analyse

# Code style
composer format
```

## Documentation

Full documentation available in the `docs/` directory:

- [Installation Guide](docs/installation.md)
- [Quick Start](docs/quickstart.md)
- [Facade API Reference](docs/facade-api.md)
- [Frontend Integration](docs/frontend-integration.md)
- [Migration Guide v1→v2](docs/migration-v1-to-v2.md)
- [Troubleshooting](docs/troubleshooting.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Martin Halaj](https://github.com/Martin-1182)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
