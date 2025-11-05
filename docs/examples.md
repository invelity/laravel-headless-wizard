---
layout: default
title: Examples
nav_order: 6
---

# Real-World Examples

Practical examples of using Laravel Headless Wizard in production applications.

---

## User Onboarding Wizard

A complete 3-step user onboarding flow with profile creation, preferences, and email verification.

### Step 1: Personal Information

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Models\User;
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
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PersonalInfoRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        $user = User::create([
            'name' => $data->get('name'),
            'email' => $data->get('email'),
            'date_of_birth' => $data->get('date_of_birth'),
        ]);

        return StepResult::success(
            data: ['user_id' => $user->id],
            message: 'Profile created successfully!'
        );
    }
}
```

---

### Step 2: Preferences (Optional)

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Models\UserPreferences;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PreferencesStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'preferences',
            title: 'Your Preferences',
            order: 2,
            isOptional: true,
            canSkip: true
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PreferencesRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        $userId = $this->getWizardData('personal-info.user_id');

        UserPreferences::create([
            'user_id' => $userId,
            'newsletter' => $data->get('newsletter', false),
            'notifications' => $data->get('notifications', true),
            'theme' => $data->get('theme', 'light'),
        ]);

        return StepResult::success(
            message: 'Preferences saved!'
        );
    }
}
```

---

### Step 3: Email Verification

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Mail\VerificationEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class EmailVerificationStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'email-verification',
            title: 'Verify Your Email',
            order: 3,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\EmailVerificationRequest::class;
    }

    public function getDependencies(): array
    {
        return ['personal-info'];
    }


    public function beforeProcess(StepData $data): void
    {
        $userId = $this->getWizardData('personal-info.user_id');
        $user = User::find($userId);

        Mail::to($user)->send(new VerificationEmail());
    }

    public function process(StepData $data): StepResult
    {
        $userId = $this->getWizardData('personal-info.user_id');
        $user = User::find($userId);

        if ($user->verification_code !== $data->get('verification_code')) {
            return StepResult::failure(
                message: 'Invalid verification code',
                errors: ['verification_code' => ['The code is incorrect']]
            );
        }

        $user->update(['email_verified_at' => now()]);

        return StepResult::success(
            message: 'Email verified successfully!'
        );
    }
}
```

---

## E-Commerce Checkout Wizard

A complete checkout flow with cart, shipping, payment, and confirmation.

### Step 1: Cart Review

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Models\Cart;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class CartReviewStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'cart-review',
            title: 'Review Cart',
            order: 1,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\CartReviewRequest::class;
    }

    public function shouldSkip(array $wizardData): bool
    {
        $cart = Cart::where('user_id', auth()->id())->first();
        return $cart?->items()->count() === 0;
    }


    public function process(StepData $data): StepResult
    {
        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        
        foreach ($data->get('items', []) as $item) {
            $cart->items()->updateOrCreate(
                ['product_id' => $item['id']],
                ['quantity' => $item['quantity']]
            );
        }

        return StepResult::success(
            data: ['cart_id' => $cart->id],
            message: 'Cart updated'
        );
    }
}
```

---

### Step 2: Shipping Address

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Models\ShippingAddress;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class ShippingAddressStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'shipping-address',
            title: 'Shipping Address',
            order: 2,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\ShippingAddressRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        $address = ShippingAddress::create([
            'user_id' => auth()->id(),
            'street' => $data->get('street'),
            'city' => $data->get('city'),
            'state' => $data->get('state'),
            'zip' => $data->get('zip'),
            'country' => $data->get('country'),
        ]);

        return StepResult::success(
            data: ['address_id' => $address->id],
            message: 'Shipping address saved'
        );
    }
}
```

---

### Step 3: Payment

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use App\Services\PaymentGateway;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PaymentStep extends AbstractStep
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway
    ) {
        parent::__construct(
            id: 'payment',
            title: 'Payment',
            order: 3,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PaymentRequest::class;
    }

    public function getDependencies(): array
    {
        return ['cart-review', 'shipping-address'];
    }


    public function process(StepData $data): StepResult
    {
        $cartId = $this->getWizardData('cart-review.cart_id');
        $addressId = $this->getWizardData('shipping-address.address_id');

        try {
            $payment = $this->paymentGateway->charge([
                'cart_id' => $cartId,
                'address_id' => $addressId,
                'payment_method' => $data->get('payment_method'),
                'card_number' => $data->get('card_number'),
                'card_exp' => $data->get('card_exp'),
                'card_cvv' => $data->get('card_cvv'),
            ]);

            return StepResult::success(
                data: ['payment_id' => $payment->id],
                message: 'Payment processed successfully'
            );
        } catch (\Exception $e) {
            return StepResult::failure(
                message: 'Payment failed: ' . $e->getMessage()
            );
        }
    }
}
```

---

## Survey Wizard with Conditional Logic

A dynamic survey that shows/hides questions based on previous answers.

### Step 1: Basic Info

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class BasicInfoStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'basic-info',
            title: 'Basic Information',
            order: 1,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\BasicInfoRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Basic information saved'
        );
    }
}
```

---

### Step 2: Employment Details (Conditional)

```php
<?php

namespace App\Wizards\OnboardingWizard\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class EmploymentDetailsStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'employment-details',
            title: 'Employment Details',
            order: 2,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\EmploymentDetailsRequest::class;
    }

    public function shouldSkip(array $wizardData): bool
    {
        return $wizardData['basic-info']['employment_status'] !== 'employed';
    }


    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Employment details saved'
        );
    }
}
```

---

## Complete Demo: Registration Wizard

This is a complete working example from the demo application showing both Blade and Vue implementations.

### Project Structure

```
app/
└── Wizards/
    └── RegistrationWizard/
        └── Steps/
            ├── PersonalInfoStep.php
            ├── PreferencesStep.php
            └── SummaryStep.php
            
app/Http/
├── Controllers/
│   └── WizardViewController.php
└── Requests/
    └── Wizards/
        ├── PersonalInfoRequest.php
        └── PreferencesRequest.php
```

### Steps Implementation

#### PersonalInfoStep.php

```php
<?php

namespace App\Wizards\RegistrationWizard\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PersonalInfoStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'personal-info',
            title: 'Personal Info',
            order: 1,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PersonalInfoRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Step completed successfully'
        );
    }
}
```

#### PreferencesStep.php

```php
<?php

namespace App\Wizards\RegistrationWizard\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PreferencesStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'preferences',
            title: 'Preferences',
            order: 2,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return \App\Http\Requests\Wizards\PreferencesRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Step completed successfully'
        );
    }
}
```

#### SummaryStep.php

```php
<?php

namespace App\Wizards\RegistrationWizard\Steps;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class SummaryStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'summary',
            title: 'Summary',
            order: 3,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return null; // No validation needed for summary
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Ready to complete'
        );
    }
}
```

### FormRequest Validators

#### PersonalInfoRequest.php

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
            'email' => ['required', 'email', 'max:255'],
            'age' => ['required', 'integer', 'min:18'],
        ];
    }
}
```

#### PreferencesRequest.php

```php
<?php

namespace App\Http\Requests\Wizards;

use Illuminate\Foundation\Http\FormRequest;

class PreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['required', 'in:light,dark,auto'],
            'notifications' => ['required', 'array'],
            'notifications.email' => ['required', 'boolean'],
            'notifications.sms' => ['required', 'boolean'],
        ];
    }
}
```

### Controller (Supports Both Blade and Vue)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;

class WizardViewController extends Controller
{
    public function __construct(
        private readonly WizardManagerInterface $wizardManager
    ) {}

    public function show(string $wizard, string $step)
    {
        $this->wizardManager->initialize($wizard);
        
        $wizardData = $this->wizardManager->getAllData();
        
        return view("wizards.steps.{$step}", [
            'wizardData' => $wizardData,
        ]);
    }

    public function store(Request $request, string $wizard, string $step)
    {
        $this->wizardManager->initialize($wizard);
        
        $result = $this->wizardManager->processStep($step, $request->all());
        
        if (!$result->success) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $result->errors,
                ], 422);
            }
            return back()->withErrors($result->errors)->withInput();
        }
        
        $currentStep = $this->wizardManager->getCurrentStep();
        
        if ($request->expectsJson()) {
            if ($currentStep) {
                return response()->json([
                    'success' => true,
                    'completed' => false,
                    'next_step' => $currentStep->getId(),
                    'data' => $result->data,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'completed' => true,
                'data' => $result->data,
            ]);
        }
        
        if ($currentStep) {
            return redirect()->route('wizard.show', [
                'wizard' => $wizard,
                'step' => $currentStep->getId(),
            ]);
        }
        
        return redirect()->route('wizard.show', [
            'wizard' => $wizard,
            'step' => 'summary',
        ]);
    }
}
```

### Routes

```php
// routes/web.php

use App\Http\Controllers\WizardViewController;

Route::prefix('wizard')->group(function () {
    Route::get('/{wizard}/{step}', [WizardViewController::class, 'show'])
        ->name('wizard.show');
    
    Route::post('/{wizard}/{step}', [WizardViewController::class, 'store'])
        ->name('wizard.store');
});

// Demo routes
Route::get('/blade/demo', function () {
    return redirect()->route('wizard.show', [
        'wizard' => 'registration',
        'step' => 'personal-info'
    ]);
});

Route::get('/vue/demo', function () {
    return view('vue-demo');
});
```

### CSRF Configuration (for Vue/API)

```php
// bootstrap/app.php

->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'wizard/*',
    ]);
})
```

### Environment Configuration

```env
# Use file-based sessions for wizard state persistence
SESSION_DRIVER=file
```

---

## Frontend Integration Examples

### React + Axios

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

function OnboardingWizard() {
    const [step, setStep] = useState(null);
    const [formData, setFormData] = useState({});
    const [progress, setProgress] = useState({});

    useEffect(() => {
        fetchCurrentStep();
    }, []);

    const fetchCurrentStep = async () => {
        const response = await axios.get('/wizard/onboarding');
        setStep(response.data.step);
        setProgress(response.data.progress);
    };

    const submitStep = async (stepId, data) => {
        try {
            const response = await axios.post(`/wizard/onboarding/${stepId}`, data);
            
            if (response.data.success) {
                if (response.data.next_step) {
                    setStep(response.data.next_step);
                } else {
                    alert('Wizard completed!');
                }
                setProgress(response.data.progress);
            }
        } catch (error) {
            console.error('Validation errors:', error.response.data.errors);
        }
    };

    return (
        <div>
            <h1>{step?.title}</h1>
            <div className="progress">
                {progress.percentage}% complete
            </div>
            <form onSubmit={(e) => {
                e.preventDefault();
                submitStep(step.id, formData);
            }}>
                {/* Form fields */}
                <button type="submit">Next</button>
            </form>
        </div>
    );
}
```

---

### Vue 3 + Composition API

```vue
<template>
  <div class="wizard">
    <h1>{{ step?.title }}</h1>
    
    <div class="progress-bar">
      <div 
        class="progress-fill" 
        :style="{ width: progress.percentage + '%' }"
      ></div>
    </div>

    <form @submit.prevent="submitStep">
      <!-- Dynamic form fields based on step.id -->
      <component :is="stepComponent" v-model="formData" />
      
      <div class="buttons">
        <button 
          type="button" 
          @click="goBack" 
          :disabled="!navigation.can_go_back"
        >
          Back
        </button>
        <button type="submit">Next</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const step = ref(null);
const progress = ref({});
const navigation = ref({});
const formData = ref({});

const stepComponent = computed(() => {
  const componentMap = {
    'personal-info': PersonalInfoForm,
    'preferences': PreferencesForm,
    'email-verification': EmailVerificationForm
  };
  return componentMap[step.value?.id];
});

onMounted(async () => {
  const response = await axios.get('/wizard/onboarding');
  step.value = response.data.step;
  progress.value = response.data.progress;
  navigation.value = response.data.navigation;
});

const submitStep = async () => {
  try {
    const response = await axios.post(
      `/wizard/onboarding/${step.value.id}`,
      formData.value
    );
    
    if (response.data.success) {
      step.value = response.data.next_step;
      progress.value = response.data.progress;
      navigation.value = response.data.navigation;
      formData.value = {};
    }
  } catch (error) {
    console.error(error.response.data.errors);
  }
};

const goBack = async () => {
  const response = await axios.get(`/wizard/onboarding/${navigation.value.previous_step.id}`);
  step.value = response.data.step;
  navigation.value = response.data.navigation;
};
</script>
```

---

### Livewire Component

```php
<?php

namespace App\Livewire;

use Invelity\WizardPackage\Facades\Wizard;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public $currentStep;
    public $progress;
    public $formData = [];
    
    public function mount()
    {
        Wizard::initialize('onboarding');
        $this->currentStep = Wizard::getCurrentStep();
        $this->progress = Wizard::getProgress();
    }

    public function submitStep()
    {
        $result = Wizard::processStep(
            $this->currentStep->getId(),
            $this->formData
        );

        if ($result->isSuccess()) {
            $nextStep = Wizard::getNextStep();
            
            if ($nextStep) {
                $this->currentStep = $nextStep;
                $this->formData = [];
            } else {
                Wizard::complete();
                return redirect()->route('dashboard');
            }
        } else {
            $this->addError('form', $result->message());
        }

        $this->progress = Wizard::getProgress();
    }

    public function render()
    {
        return view('livewire.onboarding-wizard');
    }
}
```

---

## Next Steps

- [View API Reference](api-reference)
- [Learn Testing](testing)
- [Back to Creating Wizards](creating-wizards)
