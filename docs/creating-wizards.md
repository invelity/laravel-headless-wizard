---
layout: default
title: Wizards and Steps
nav_order: 4
---

# Wizards and steps

## Defining a wizard

A wizard is a class that extends `Invelity\WizardPackage\Wizard` and lists its steps in order:

```php
namespace App\Wizards;

use App\Wizards\Steps\BillingStep;
use App\Wizards\Steps\CalculatorStep;
use App\Wizards\Steps\ConfirmationStep;
use App\Wizards\Steps\NewsletterStep;
use App\Wizards\Steps\PersonalDataStep;
use App\Wizards\Steps\SummaryStep;
use Invelity\WizardPackage\Wizard;

class OrderWizard extends Wizard
{
    protected array $steps = [
        CalculatorStep::class,
        PersonalDataStep::class,
        NewsletterStep::class,
        BillingStep::class,
        SummaryStep::class,
        ConfirmationStep::class,
    ];
}
```

| Property | Default | Meaning |
| --- | --- | --- |
| `$steps` | `[]` | The step classes, in order. They are resolved through the container, so they may inject services. |
| `$name` | kebab-cased class name without `Wizard` (`order`) | Identifies the wizard in stores, route names and events. |
| `$store` | `null` (the default store) | The store that keeps the state. |
| `$allowJumping` | `false` | Lets the visitor open any step regardless of order. Dependencies still apply. |

Generate one with `php artisan wizard:make OrderWizard`.

## Using a wizard

Type-hint the wizard anywhere the container resolves arguments: controllers, jobs, Livewire components. Or call
`Wizard::for()`. Either way you get a fresh instance bound to the current visitor.

```php
public function show(OrderWizard $wizard, string $step) { /* ... */ }

$wizard = Wizard::for(OrderWizard::class);
$wizard = Wizard::for(OrderWizard::class, $user);   // another visitor, with the cache or database store
```

Reading never writes. The state is stored the first time something changes, and `WizardStarted` is dispatched then.

## Defining a step

A step extends `Invelity\WizardPackage\Step`:

```php
namespace App\Wizards\Steps;

use App\Http\Requests\Wizards\CalculatorRequest;
use App\Services\PriceCalculator;
use Invelity\WizardPackage\Step;

class CalculatorStep extends Step
{
    protected ?string $formRequest = CalculatorRequest::class;

    public function handle(array $data, PriceCalculator $prices): array
    {
        return [...$data, 'price' => $prices->quote($data['weight'])];
    }
}
```

| Property or method | Default | Meaning |
| --- | --- | --- |
| `$id` / `id()` | kebab-cased class name without `Step` (`calculator`) | Identifies the step within the wizard and in URLs. |
| `$title` / `title()` | headline of the class name (`Calculator`) | Shown in navigation. Override `title()` to translate it. |
| `$formRequest` | `null` | The form request that validates the input. Without one the step accepts no input. |
| `$optional` | `false` | The visitor may skip the step. |
| `$displayOnly` | `false` | The step only shows information (a confirmation page). |
| `$dependencies` | `[]` | Step classes that must be finished first; see [reopening](#dependencies-and-reopening). |
| `shouldSkip(State $state)` | `false` | Leaves the step out of the flow for this state; see [conditional steps](#conditional-steps). |
| `handle()` | stores the validated data | Called through the container with `$data` (and `$wizard`). Returns the array to store, or nothing to store the validated data. |

Generate one with `php artisan wizard:make-step CalculatorStep --wizard=OrderWizard`. Add `--view` to also create its
Blade view with Laravel's `make:view`. The view is named after the wizard and the step (`order.calculator`, so
`resources/views/order/calculator.blade.php`), and `--view=checkout.payment` picks another name.

## Validation

`process()` validates the step's input exactly like Laravel validates a type-hinted form request.

- **The whole lifecycle runs:** `prepareForValidation()`, `authorize()`, the rules, the `after()` hooks and
  `passedValidation()`.
- **The request knows the context:** `$this->user()` and `$this->route()` work, and `$this->input()` contains the
  submitted data.
- **Only `validated()` data is stored.**
- **Failures throw Laravel's exceptions:** a `ValidationException` (422 for JSON, otherwise a redirect back with errors)
  or an `AuthorizationException` (403).

```php
class PersonalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}
```

`process()` accepts the request itself, which keeps uploaded files, or a plain array:

```php
$wizard->process('personal-data', $request);
$wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);
```

Uploaded files reach `handle()` as `UploadedFile` objects. Store them there and return their paths, because the state
must stay serialisable.

## Moving through the wizard

```php
$wizard->process('calculator', $request);   // validate, store, move on
$wizard->skip('newsletter');                // only optional steps
$wizard->goTo('calculator');                // move back to an accessible step
$wizard->current();                         // the step the visitor should be on
$wizard->next('calculator');                // relative to any step, or to the current one
$wizard->previous();
$wizard->firstUnfinished();                 // where to resume
$wizard->canAccess('summary');
```

A step is accessible when it takes part in the flow, its dependencies are finished, and every required step before it is
finished. Optional and display-only steps never block the steps after them. Processing or skipping a step the visitor
may not open throws `StepNotAccessibleException` (403).

## Optional steps

```php
class NewsletterStep extends Step
{
    protected bool $optional = true;
}
```

The visitor may `skip()` it. Skipped steps count as finished for progress and completion.

## Conditional steps

Leave a step out of the flow based on the state:

```php
class BillingStep extends Step
{
    public function shouldSkip(State $state): bool
    {
        return ($state->data('plan')['plan'] ?? null) === 'free';
    }
}
```

A step left out does not appear in the navigation, does not count towards progress and never blocks completion.

## Dependencies and reopening

A step can depend on earlier steps:

```php
class SummaryStep extends Step
{
    protected array $dependencies = [CalculatorStep::class, PersonalDataStep::class];
}
```

The summary opens only once both are finished. When the visitor processes the calculator again **with different data**,
the summary is reopened: it must be submitted again. Its previous data is kept for prefilling, and `StepReopened` is
dispatched. Processing the calculator with the same data changes nothing.

To reopen a step and its dependents yourself, call `reopen()`. It also moves the visitor there:

```php
$wizard->reopen('calculator');
```

## Display-only steps

A confirmation page, a "what happens next" page or an info screen:

```php
class ConfirmationStep extends Step
{
    protected bool $displayOnly = true;
}
```

It takes no input, so processing it is refused, and it never counts towards progress or completion. It becomes
accessible once the required steps before it are finished. After `complete()` the visitor lands on the last display-only
step.

## Completing

```php
$data = $wizard->complete();
```

- **Requirement:** every required step must be finished; otherwise a `ValidationException` lists the missing steps.
- **On success:** `WizardCompleted` is dispatched with the data of every step.
- **Afterwards:** a completed wizard refuses further input with `WizardAlreadyCompletedException` (409), so completing
  twice cannot create a duplicate order. `reopen()` makes it editable again; `reset()` starts over.

## Metadata

Keep data that belongs to the wizard but not to a step, such as the chosen carrier or a basket, as metadata:

```php
$wizard->putMetadata('carrier', 'gls');
$wizard->putMetadata(['parcels.0.weight' => 2.5, 'cash_on_delivery' => true]);
$wizard->metadata('parcels.0.weight');       // 2.5
$wizard->forgetMetadata('cash_on_delivery');
$wizard->start(['source' => 'landing-page']); // initial metadata, only when the wizard starts
```

## Events

| Event | When | Payload |
| --- | --- | --- |
| `WizardStarted` | the state is stored for the first time | `wizard`, `scope`, `metadata` |
| `StepCompleted` | a step was processed | `wizard`, `scope`, `step`, `data`, `percentage` |
| `StepSkipped` | an optional step was skipped | `wizard`, `scope`, `step` |
| `StepReopened` | a finished step was reopened | `wizard`, `scope`, `step` |
| `WizardCompleted` | the wizard was completed | `wizard`, `scope`, `data` |
| `WizardReset` | the state was removed | `wizard`, `scope` |

`wizard` is the wizard class, so a queued listener can load it again:

```php
public function handle(WizardCompleted $event): void
{
    $wizard = Wizard::for($event->wizard, $event->scope);
}
```
