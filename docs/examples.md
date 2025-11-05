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
            isOptional: false
        );
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
            isOptional: false
        );
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
            order: 1
        );
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
            order: 2
        );
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
            order: 3
        );
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
            order: 1
        );
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
            order: 2
        );
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
