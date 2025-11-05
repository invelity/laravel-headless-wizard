# Package Improvement Suggestions

Analýza možných vylepšení Laravel Headless Wizard package pre jednoduchšiu developer experience.

## 1. Artisan Commands

### `wizard:make` Command
**Problém**: Developer musí manuálne vytvárať celú štruktúru (Steps, Requests, Views)

**Riešenie**: Interaktívny príkaz pre generovanie wizardu

**CSRF Exception Handling**:
Package **nemôže** automaticky registrovať CSRF exceptions (v Laravel 11+ je to v `bootstrap/app.php`).
Riešenie: `wizard:make` command vypíše **post-install instructions**:

```
✅ Wizard created successfully!

⚠️  IMPORTANT: For API/Vue/React wizards, add CSRF exception:

   File: bootstrap/app.php
   
   ->withMiddleware(function (Middleware $middleware): void {
       $middleware->validateCsrfTokens(except: [
           'wizard/registration/*',  // <-- Add this line
       ]);
   })

📖 See documentation: https://wizard-docs.com/csrf
```

```bash
php artisan wizard:make RegistrationWizard
```

**Interakcia**:
```
What type of wizard do you want to create?
  [1] Blade (traditional server-side)
  [2] Vue/React SPA (headless API)
  [3] Livewire
  [4] Inertia.js
 > 1

How many steps? > 3

Step 1 name: Personal Information
Step 2 name: Preferences  
Step 3 name: Summary

Generate example views? (yes/no) [yes]: yes
```

**Vytvorí**:
```
app/Wizards/RegistrationWizard/
├── Steps/
│   ├── PersonalInformationStep.php
│   ├── PreferencesStep.php
│   └── SummaryStep.php
├── Requests/
│   ├── PersonalInformationRequest.php
│   └── PreferencesRequest.php
└── RegistrationWizard.php (konfiguračný súbor)

resources/views/wizards/registration/
├── personal-information.blade.php
├── preferences.blade.php
└── summary.blade.php

app/Http/Controllers/
└── RegistrationWizardController.php

routes/web.php (automaticky pridá routes)
```

### `wizard:step` Command
**Problém**: Pridanie nového stepu vyžaduje manuálne editovanie

**Riešenie**:
```bash
php artisan wizard:step RegistrationWizard AddressStep --order=3
```

Automaticky:
- Vytvorí Step class s správnym order
- Vytvorí FormRequest
- Vytvorí Blade view (ak Blade wizard)
- Aktualizuje ostatné steps (zvýši order ak potrebné)

## 2. Auto-Discovery a Routing

### Problém: Manuálna registrácia routes
Developer musí sám definovať routes pre každý wizard.

### Riešenie: Auto-registration routes

**V ServiceProvider**:
```php
public function boot(): void
{
    if ($this->app->config['wizard.auto_register_routes']) {
        $this->registerWizardRoutes();
    }
}

protected function registerWizardRoutes(): void
{
    $wizardPath = app_path('Wizards');
    
    foreach (glob("$wizardPath/*") as $wizardDir) {
        $wizardName = basename($wizardDir);
        $wizardId = Str::kebab($wizardName);
        
        Route::prefix("wizard/{$wizardId}")
            ->name("wizard.{$wizardId}.")
            ->group(function () use ($wizardName) {
                Route::get('/{step}', [WizardController::class, 'show'])
                    ->name('show');
                Route::post('/{step}', [WizardController::class, 'store'])
                    ->name('store');
                Route::post('/complete', [WizardController::class, 'complete'])
                    ->name('complete');
            });
    }
}
```

**Výsledok**:
- `wizard.registration.show`
- `wizard.registration.store`
- `wizard.registration.complete`

Automaticky pre každý wizard v `app/Wizards/`.

## 3. Wizard Configuration File

### Problém: Konfigurácia roztrúsená v kóde

### Riešenie: Konfiguračný súbor na wizard

**app/Wizards/RegistrationWizard/config.php**:
```php
return [
    'id' => 'registration',
    'title' => 'User Registration',
    
    // Type určuje default behavior
    'type' => 'blade', // blade, api, livewire, inertia
    
    // Storage strategy
    'storage' => 'session', // session, cache, database
    
    // Events
    'events' => [
        'enabled' => true,
        'listeners' => [
            WizardStarted::class => [SendWelcomeEmail::class],
            WizardCompleted::class => [CreateUserAccount::class, SendThankYouEmail::class],
        ],
    ],
    
    // CSRF
    'csrf' => [
        'enabled' => true, // false pre API
    ],
    
    // Redirects
    'redirects' => [
        'after_complete' => '/dashboard',
        'on_cancel' => '/',
    ],
    
    // Middleware
    'middleware' => ['web', 'auth'], // alebo ['api', 'auth:sanctum']
    
    // Views (pre Blade)
    'views' => [
        'layout' => 'wizards.registration.layout',
        'steps' => 'wizards.registration.steps',
    ],
];
```

## 4. Trait pre Controllers

### Problém: Opakujúci sa kód v controlleroch

### Riešenie: `HasWizard` trait

```php
namespace Invelity\WizardPackage\Traits;

trait HasWizard
{
    protected string $wizardId;
    
    public function show(string $step)
    {
        $this->initializeWizard();
        
        return $this->renderStep($step);
    }
    
    public function store(Request $request, string $step)
    {
        $this->initializeWizard();
        
        $result = $this->processStep($step, $request->all());
        
        return $this->handleStepResult($result, $request);
    }
    
    protected function renderStep(string $step)
    {
        $config = $this->getWizardConfig();
        
        return match($config['type']) {
            'blade' => view("{$config['views']['steps']}.{$step}", [
                'wizardData' => $this->wizardManager->getAllData(),
            ]),
            'api' => response()->json([
                'step' => $step,
                'data' => $this->wizardManager->getAllData(),
            ]),
        };
    }
    
    // ... helper methods
}
```

**Použitie**:
```php
class RegistrationWizardController extends Controller
{
    use HasWizard;
    
    protected string $wizardId = 'registration';
    
    // Hotovo! Všetko ostatné je v trait.
}
```

## 5. Blade Components

### Problém: Opakujúci sa markup pre progress bar, navigáciu

### Riešenie: Blade components

```bash
php artisan vendor:publish --tag="wizard-views"
```

**Vytvorí**:
```
resources/views/vendor/wizard/components/
├── progress-bar.blade.php
├── step-navigation.blade.php
├── form-wrapper.blade.php
└── layout.blade.php
```

**Použitie**:
```blade
<x-wizard::layout :wizard="$wizard">
    <x-wizard::progress-bar :steps="$steps" :current="$currentStep" />
    
    <form method="POST" action="{{ route('wizard.store', [$wizard, $step]) }}">
        @csrf
        
        <!-- Your form fields -->
        
        <x-wizard::step-navigation 
            :can-go-back="$currentStepIndex > 0"
            :is-last-step="$currentStepIndex === count($steps) - 1"
        />
    </form>
</x-wizard::layout>
```

## 6. Vue/React Composables

### Problém: Duplicitný kód v SPA implementáciách

### Riešenie: Publikovanie composables

**Vue Composable**:
```javascript
// resources/js/composables/useWizard.js

export function useWizard(wizardId) {
  const currentStepIndex = ref(0);
  const steps = ref([]);
  const formData = reactive({});
  const errors = ref({});
  const loading = ref(false);

  async function submitStep() {
    errors.value = {};
    loading.value = true;

    try {
      const response = await axios.post(
        `/api/wizard/${wizardId}/${steps.value[currentStepIndex.value].id}`,
        getStepData()
      );

      if (response.data.completed) {
        return { completed: true, data: response.data };
      }

      if (response.data.next_step) {
        goToStep(response.data.next_step);
      }
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors;
      }
    } finally {
      loading.value = false;
    }
  }

  function goToStep(stepId) {
    const index = steps.value.findIndex(s => s.id === stepId);
    if (index !== -1) {
      currentStepIndex.value = index;
    }
  }

  return {
    currentStepIndex,
    currentStep: computed(() => steps.value[currentStepIndex.value]),
    steps,
    formData,
    errors,
    loading,
    submitStep,
    goToStep,
  };
}
```

**Použitie**:
```vue
<script setup>
import { useWizard } from '@/composables/useWizard';

const { currentStep, formData, errors, submitStep } = useWizard('registration');
</script>
```

## 7. Validation Rules Provider

### Problém: Duplikácia validačných pravidiel medzi backend a frontend

### Riešenie: API endpoint pre pravidlá

```php
Route::get('/wizard/{wizard}/validation-rules', function (string $wizard) {
    $wizardManager = app(WizardManagerInterface::class);
    $wizardManager->initialize($wizard);
    
    $rules = [];
    foreach ($wizardManager->getSteps() as $step) {
        $formRequest = $step->getFormRequest();
        $rules[$step->getId()] = (new $formRequest)->rules();
    }
    
    return response()->json($rules);
});
```

**Vue/React môže importovať pravidlá**:
```javascript
const rules = await axios.get(`/wizard/registration/validation-rules`);

// Client-side validation pred submitom
```

## 8. Step Dependencies & Conditional Steps

### Problém: Nemožnosť definovať závislosti medzi stepmi

### Riešenie: Rozšírenie AbstractStep

```php
abstract class AbstractStep
{
    public function shouldShow(array $wizardData): bool
    {
        return true; // Override v konkrétnom stepe
    }
    
    public function getDependencies(): array
    {
        return []; // ['personal-info', 'preferences']
    }
}
```

**Príklad**:
```php
class BusinessDetailsStep extends AbstractStep
{
    public function shouldShow(array $wizardData): bool
    {
        // Zobraz len ak user type je 'business'
        return ($wizardData['personal-info']['user_type'] ?? '') === 'business';
    }
    
    public function getDependencies(): array
    {
        return ['personal-info'];
    }
}
```

## 9. Progress Persistence & Resume

### Problém: Používateľ stratí progress pri zatvorení browsera (session storage)

### Riešenie: Database persistence s token URL

**Automatické uloženie do DB**:
```php
// config/wizard.php
'persistence' => [
    'enabled' => true,
    'driver' => 'database', // database, cache
    'table' => 'wizard_progress',
    'ttl' => 60 * 24 * 7, // 7 dní
],
```

**Generovanie resume URL**:
```php
$wizard->getResumeUrl(); 
// https://app.test/wizard/registration/resume/abc123token
```

**Use case**: Email s odkazom na dokončenie registrácie

## 10. Multi-tenancy Support

### Problém: V multi-tenant aplikáciách potrebuješ izolovať wizard data

### Riešenie: Tenant-aware storage

```php
// config/wizard.php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
    'tenant_resolver' => fn() => auth()->user()->tenant_id,
],
```

## 11. Testing Helpers

### Problém: Ťažké testovanie wizard flow

### Riešenie: Testing trait

```php
use Invelity\WizardPackage\Testing\InteractsWithWizard;

class RegistrationWizardTest extends TestCase
{
    use InteractsWithWizard;
    
    public function test_complete_registration_flow()
    {
        $this->startWizard('registration')
            ->submitStep('personal-info', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 25,
            ])
            ->assertStepCompleted('personal-info')
            ->assertCurrentStep('preferences')
            ->submitStep('preferences', [
                'theme' => 'dark',
                'notifications' => ['email' => true, 'sms' => false],
            ])
            ->assertWizardCompleted()
            ->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }
}
```

## 12. Analytics & Metrics

### Problém: Nevieme kde users dropujú

### Riešenie: Built-in analytics events

```php
// Automaticky trackuje:
- WizardStarted
- StepCompleted  
- StepSkipped
- StepFailed (validation error)
- WizardAbandoned (timeout)
- WizardCompleted

// config/wizard.php
'analytics' => [
    'enabled' => true,
    'drivers' => ['database', 'google-analytics'],
],
```

**Dashboard**:
```
Registration Wizard - Last 30 days
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Started:           1,000
Completed:           750 (75%)
Drop-off by step:
  Personal Info:    50 (5%)
  Preferences:     150 (15%)
  Summary:          50 (5%)
```

## Prioritizácia

### High Priority (must have)
1. ✅ `wizard:make` command - Najväčší impact na DX
2. ✅ Blade components - Výrazne znižuje boilerplate
3. ✅ `HasWizard` trait - Eliminuje opakujúci sa controller kód
4. ✅ Vue composable - Štandardizuje SPA implementácie

### Medium Priority (nice to have)
5. Auto-registration routes - Zlepšuje convention over configuration
6. Configuration file per wizard - Centralizuje nastavenia
7. Testing helpers - Uľahčuje TDD
8. Step dependencies - Enables complex flows

### Low Priority (future)
9. Analytics dashboard - Užitočné ale nie kritické
10. Multi-tenancy - Špecifický use case
11. Progress persistence - Edge case (môže byť addon)
12. Validation rules API - Optimization, nie nevyhnutné

## Implementačný plán

### Fáza 1: Core DX Improvements (Sprint 1-2)
- [ ] `wizard:make` command s templates
- [ ] `wizard:step` command  
- [ ] `HasWizard` trait pre controllers
- [ ] Blade components (layout, progress, navigation)
- [ ] Auto-registration routes (opt-in)

### Fáza 2: Frontend Support (Sprint 3)
- [ ] Vue composable `useWizard`
- [ ] React hook `useWizard`
- [ ] Publikovanie frontend assets
- [ ] Validation rules API endpoint

### Fáza 3: Advanced Features (Sprint 4-5)
- [ ] Configuration file per wizard
- [ ] Conditional steps (shouldShow)
- [ ] Step dependencies
- [ ] Testing helpers trait

### Fáza 4: Enterprise Features (Backlog)
- [ ] Analytics & metrics
- [ ] Multi-tenancy support
- [ ] Progress persistence
- [ ] Resume functionality

## Backward Compatibility

Všetky nové features by mali byť **opt-in** pomocou config flags:

```php
// config/wizard.php
return [
    'auto_register_routes' => false, // BC: false by default
    'blade_components' => true,
    'analytics' => false,
    'multi_tenancy' => false,
];
```

Existujúce implementácie budú fungovať bez zmien.
