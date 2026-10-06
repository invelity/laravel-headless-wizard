# Upgrade Guide

## Upgrading to 2.0 from 1.x

2.0 rebuilds the package the way Laravel itself is built. Wizards and steps are classes and step input is validated by
form requests with their whole lifecycle. State is kept per visitor in configurable stores. Routes are opt-in.

Most applications need an hour or two. The work is mechanical, and this guide maps every 1.x class, method, config key
and event to its replacement.

Sessions written by 1.x keep working: the session store reads the old records, including keys your application added
itself, so visitors in the middle of a wizard keep their progress through the deploy.

### Requirements

- PHP 8.4 or higher
- Laravel 12 or 13 (Laravel 11 stays supported by 1.x)

```bash
composer require "invelity/laravel-headless-wizard:^2.0"
```

### Configuration

Replace `config/wizard.php` with the new file:

```bash
php artisan vendor:publish --tag=wizard-config --force
```

| 1.x key | 2.x |
| --- | --- |
| `storage.driver`, `WIZARD_STORAGE` | `default`, `WIZARD_STORE` |
| `storage.ttl`, `cache.*` | `stores.cache.ttl`, `stores.cache.store`, `stores.cache.prefix` |
| `database.*` | `stores.database.connection`, `stores.database.table`, `stores.database.encrypt` |
| `session.*` | `stores.session.prefix` (default `wizard_`, as in 1.x) |
| `wizards` (discovered) | removed; a wizard is referenced by its class |
| `routes.*`, `route.*` | removed; register routes with `Route::wizard()` |
| `navigation.allow_jump` | `protected bool $allowJumping` on the wizard |
| `navigation.show_all_steps`, `navigation.mark_completed`, `validation.*`, `events.*`, `cleanup.*` | removed; they were never read |

### Wizards

1.x discovered `app/Wizards/{Name}Wizard/{Name}.php` and steps in its `Steps` folder. 2.x has no discovery: a wizard is a
class that lists its steps.

```php
// 1.x: app/Wizards/OrderWizard/Order.php
class Order
{
    public const ID = 'order-wizard';

    public function getId(): string
    {
        return self::ID;
    }
}

// 2.x: app/Wizards/OrderWizard.php
use Invelity\WizardPackage\Wizard;

class OrderWizard extends Wizard
{
    // Keep the 1.x id, so the session key stays "wizard_order-wizard" and running sessions survive.
    protected string $name = 'order-wizard';

    protected array $steps = [
        CalculatorStep::class,
        PersonalDataStep::class,
        SummaryStep::class,
        ConfirmationStep::class,
    ];
}
```

The order of `$steps` is the order of the wizard; the `order` of each step is gone.

### Steps

| 1.x | 2.x |
| --- | --- |
| `extends AbstractStep` | `extends Invelity\WizardPackage\Step` |
| `parent::__construct(id:, title:, order:, isOptional:, canSkip:)` | `protected string $id`, `protected string $title`, `protected bool $optional` (both default from the class name) |
| `getFormRequest(): ?string` | `protected ?string $formRequest` |
| `process(StepData $data): StepResult` | optional `handle(array $data, ...services): array`, whose result is stored |
| `StepResult::success($data)` | `return $data;` from `handle()` |
| `StepResult::failure($errors)` | `throw ValidationException::withMessages($errors);` |
| `beforeProcess()`, `afterProcess()` | `prepareForValidation()`/`passedValidation()` on the form request, or code in `handle()` |
| `getDependencies()` returning ids | `protected array $dependencies` listing step **classes**; changing a dependency reopens the step |
| `shouldSkip(array $wizardData)` | `shouldSkip(State $state)`; use `$state->data('calculator')`, `$state->metadata` |
| `getId()`, `getTitle()`, `isOptional()`, `canSkip()` | `id()`, `title()`, `isOptional()` |
| a step that only shows information | `protected bool $displayOnly = true;` |

Constructor injection is no longer tangled with the parent constructor; type-hint services on the step's constructor
or on `handle()`.

### Validation

1.x instantiated the form request with `new` and only called `rules()`, `messages()` and `attributes()`. 2.x resolves it
like Laravel resolves a type-hinted form request: `prepareForValidation()`, `authorize()`, `after()` hooks and
`passedValidation()` run, and `$this->user()`, `$this->route()` and `$this->input()` work inside `rules()`.

Only `validated()` data is stored. A step without a form request accepts no input.

If you validated each step a second time in your own request class to work around 1.x, delete that request.

### Using a wizard

`WizardManagerInterface` and the other 1.x interfaces are gone. Inject the wizard class, or use `Wizard::for()`.

```php
// 1.x
$manager->initialize('order-wizard');
$result = $manager->processStep('calculator', $request->all());

// 2.x
public function store(Request $request, OrderWizard $wizard, string $step)
{
    $wizard->process($step, $request);
}

// or, outside the container
Wizard::for(OrderWizard::class)->process('calculator', $data);
Wizard::for(OrderWizard::class, $user)->data();   // another visitor's wizard (cache or database store)
```

| 1.x `WizardManagerInterface` | 2.x wizard instance |
| --- | --- |
| `initialize($id)` | not needed |
| `initialize($id, ['metadata' => [...]])` | `start($metadata)` |
| `processStep($step, $data)` returning `StepResult` | `process($step, $request or $data)` returning the stored data, throwing `ValidationException` |
| `skipStep($step)` | `skip($step)` |
| `navigateToStep($step)` | `goTo($step)` |
| `getCurrentStep()` | `current()` |
| `getStep($id)` | `step($id)` |
| `getNextStep()`, `getPreviousStep()` | `next(?$step)`, `previous(?$step)`, relative to any step |
| `canAccessStep($step)` | `canAccess($step)` |
| `getProgress()->completionPercentage` | `progress()->percentage()` |
| `getProgress()->remainingStepIds[0]` | `firstUnfinished()?->id()` |
| `getNavigation()->getItems()` | `navigation(?$step)->items` |
| `getNavigation()->canGoBack()`, `canGoForward()` | `navigation(?$step)->canGoBack()`, `canGoForward()` |
| `getAllData()` | `data()`; `data($step)` for one step |
| `complete()` returning `StepResult` | `complete()` returning the data; throws if incomplete or already completed |
| `reset()` | `reset()` |
| `loadFromStorage($id, $instanceId)`, `deleteWizard($id, $instanceId)` | removed; `Wizard::for(OrderWizard::class, $scope)` with the database store |

`NavigationItem` is now immutable and readonly:

| 1.x | 2.x |
| --- | --- |
| `$item->stepId` | `$item->id` |
| `$item->title`, `$item->position` | unchanged |
| `$item->isCurrent()` | `$item->current` |
| `$item->isCompleted()` | `$item->status === StepStatus::Completed` |
| `$item->isAccessible`, `$item->isOptional` | `$item->accessible`, `$item->optional` |
| `$item->url` | `$item->url`, from your routes; see "URLs" below |
| dynamic properties added by the application | not possible; build your own view model from the item |

### State and metadata

Stop reading and writing the raw state through `WizardStorageInterface`:

| 1.x | 2.x |
| --- | --- |
| `$storage->get('order-wizard')['metadata']['carrier']` | `$wizard->metadata('carrier')` |
| `$storage->update('order-wizard', 'metadata.carrier', 'gls')` | `$wizard->putMetadata('carrier', 'gls')` |
| extra root keys such as `packages` | `$wizard->putMetadata('packages', [...])`; existing root keys stay readable through `$wizard->state()->attributes` |
| `$storage->update(..., 'current_step', 'summary')` | `$wizard->goTo('summary')` |
| rewriting `completed_steps` to make the visitor redo steps | `$wizard->reopen('calculator')`, or declare `$dependencies` and let it happen automatically |
| `$storage->exists('order-wizard')` | `$wizard->exists()` |
| `$storage->forget('order-wizard')` | `$wizard->reset()` |

The stored layout stays readable by 1.x-style code. `completed_steps` still lists finished steps (skipped ones
included), `steps.{id}` holds the data, `current_step` the position, and keys the package does not own are preserved. New
keys are `skipped_steps`, `version`, `completed_at` and `status`.

### Stores

The cache and database stores now keep each visitor apart. 1.x shared one state between everybody.

- **Scope:** by default it is the authenticated user, or else a random token kept in the session. Change it with
  `Wizard::resolveScopeUsing()`.
- **Database store:** it uses a new `wizard_states` table; publish the migration with `--tag=wizard-migrations`. The 1.x
  `wizard_progress` table and the `WizardProgress` model are gone. 1.x rows cannot be migrated meaningfully, because
  every visitor shared them.
- **Cleanup:** schedule `php artisan wizard:prune --days=30` to remove abandoned database states.

### Routes and middleware

1.x registered `/wizard/{wizard}/{step}`, `/complete`, `/skip` and id-based `/edit` routes for every wizard. 2.x
registers nothing by itself.

- If you used the package routes, register them per wizard with `Route::wizard('order', OrderWizard::class)` and read the
  new response shape in the docs.
- If you disabled them, there is nothing to do. Delete any middleware that blocked them.
- `wizard.session` is gone: the `web` middleware group starts the session.
- `wizard.step-access` is replaced by `wizard.step:{wizard class}` (`EnsureStepIsAccessible`). It works with your own
  routes and parameter names and redirects to the step the visitor should be on.

```php
Route::get('/order-wizard/{step}', ShowOrderStep::class)
    ->middleware(EnsureStepIsAccessible::using(OrderWizard::class));
```

### URLs

1.x navigation items always pointed at the package routes. 2.x resolves them, in this order:

1. a `stepUrl(Step $step)` method on the wizard;
2. `Wizard::resolveUrlsUsing()`;
3. the routes registered with `Route::wizard()`;
4. otherwise `null`.

```php
class OrderWizard extends Wizard
{
    protected function stepUrl(Step $step): ?string
    {
        return route('order-wizard.step', $step->id());
    }
}
```

### Events

| 1.x | 2.x |
| --- | --- |
| `WizardStarted(wizardId, userId, sessionId, initialData)` | `WizardStarted(wizard, scope, metadata)` |
| `StepCompleted(wizardId, stepId, stepData, progress)` | `StepCompleted(wizard, scope, step, data, percentage)` |
| `StepSkipped(wizardId, stepId, reason)` | `StepSkipped(wizard, scope, step)` |
| `WizardCompleted(wizardId, allData, completedAt)` | `WizardCompleted(wizard, scope, data)` |
| — | `StepReopened(wizard, scope, step)`, `WizardReset(wizard, scope)` |

`wizard` is the class of the wizard: use `Wizard::for($event->wizard, $event->scope)` in a listener to load it. Events
are always dispatched; the `events.dispatch` switch is gone.

### Exceptions

| 1.x | 2.x |
| --- | --- |
| `InvalidStepException` | `StepNotFoundException` (404) or `StepNotAccessibleException` (403) |
| `StepValidationException` | Laravel's `ValidationException` (422 or a redirect back) |
| `StepAccessDeniedException`, `WizardNotInitializedException` | removed |
| — | `WizardAlreadyCompletedException` (409), `InvalidWizardException` for definition errors |

### Removed

- The Blade components and the `useWizard()` Vue composable. Build the UI from `navigation()` and `progress()`; the docs
  have Blade and Vue recipes.
- The `Wizard` wrapper class and the `WizardPackage` facade. The `Wizard` facade remains and is now the entry point.
- The `--type` option of `wizard:make`.
- `spatie/laravel-package-tools` as a dependency.

### Testing

Use the in-memory store in your tests:

```php
config(['wizard.default' => 'array']);
```

### Checklist for applications that drive the wizard from their own controllers

- [ ] Replace `initialize()` + `processStep()` with an injected wizard and `process($step, $request)`.
- [ ] Delete any request class that re-validated the steps. The step form requests now run fully.
- [ ] Replace custom "previous steps completed" middleware with `EnsureStepIsAccessible`.
- [ ] Delete middleware that hid the 1.x package routes.
- [ ] Move raw state writes to `putMetadata()`, `goTo()` and `reopen()`, and declare `$dependencies` on steps such as a
      summary.
- [ ] Turn confirmation pages into `$displayOnly` steps. A wizard can no longer be completed twice.
- [ ] Keep the 1.x wizard id in `$name` so in-flight sessions survive the deploy.
- [ ] Replace raw `steps.{id}` and `completed_steps` reads in tests with the wizard API, or seed state through
      `Wizard::for(...)->process()`.
