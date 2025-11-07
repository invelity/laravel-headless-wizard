# Vue 3 Wizard Composable

## Installation

Publish the assets:

```bash
php artisan vendor:publish --tag=wizard-assets
```

Copy `useWizard.js` to your Vue project or import directly.

## Usage

```vue
<script setup>
import { useWizard } from './composables/useWizard';
import { onMounted } from 'vue';

const wizard = useWizard('registration', {
    apiBaseUrl: '/api/wizard',
    onComplete: (data) => {
        console.log('Wizard completed!', data);
        // Redirect or show success message
    },
    onError: (error) => {
        console.error('Wizard error:', error);
    }
});

onMounted(async () => {
    await wizard.initialize();
});

async function handleSubmit() {
    const result = await wizard.submitStep({
        name: 'John Doe',
        email: 'john@example.com'
    });

    if (!result.success && result.errors) {
        // Validation errors are automatically stored in wizard.state.errors
        console.log('Validation failed:', result.errors);
    }
}
</script>

<template>
    <div v-if="wizard.state.loading">Loading...</div>
    
    <div v-else-if="wizard.currentStep.value">
        <h2>{{ wizard.currentStep.value.title }}</h2>
        
        <!-- Progress -->
        <div>Step {{ wizard.state.currentStepIndex + 1 }} of {{ wizard.state.steps.length }}</div>
        
        <!-- Your form fields here -->
        <form @submit.prevent="handleSubmit">
            <!-- Display errors -->
            <div v-if="wizard.getFieldError('email')" class="error">
                {{ wizard.getFieldError('email') }}
            </div>
            
            <input 
                v-model="wizard.state.formData.email" 
                @input="wizard.setFieldValue('email', $event.target.value)"
            />
            
            <button type="submit" :disabled="wizard.state.loading">
                {{ wizard.isLastStep.value ? 'Complete' : 'Next' }}
            </button>
        </form>
        
        <!-- Navigation -->
        <button 
            v-if="wizard.canGoBack.value" 
            @click="wizard.goBack()"
            :disabled="wizard.state.loading"
        >
            Previous
        </button>
    </div>
    
    <div v-else-if="wizard.state.completed">
        <h2>Wizard Completed!</h2>
        <pre>{{ wizard.state.wizardData }}</pre>
    </div>
</template>
```

## API Reference

### State

- `state.currentStepIndex` - Current step index (0-based)
- `state.steps` - Array of wizard steps
- `state.formData` - Form data across all steps
- `state.errors` - Validation errors (keyed by field name)
- `state.loading` - Loading state
- `state.completed` - Whether wizard is completed
- `state.wizardData` - Final wizard data after completion

### Computed Properties

- `currentStep` - Current step object
- `canGoBack` - Whether can navigate to previous step
- `canGoForward` - Whether can navigate to next step
- `isLastStep` - Whether on the last step

### Methods

- `initialize()` - Initialize wizard (fetch steps from API)
- `submitStep(data)` - Submit current step data
- `goToStep(stepId)` - Navigate to specific step
- `goBack()` - Navigate to previous step
- `goForward()` - Navigate to next step
- `setFieldValue(field, value)` - Update form field value
- `getFieldError(field)` - Get validation error for field
- `clearErrors()` - Clear all validation errors
- `reset()` - Reset wizard to initial state

## CSRF Protection

The composable automatically includes the CSRF token from the `<meta name="csrf-token">` tag.

Make sure your Blade layout includes:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```
