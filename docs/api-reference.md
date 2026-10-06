---
layout: default
title: API Reference
nav_order: 5
---

# API reference

## The `Wizard` facade

| Method | Returns | Description |
| --- | --- | --- |
| `Wizard::for(string\|Wizard $wizard, mixed $scope = null)` | the wizard class | A fresh wizard bound to the current visitor, or to `$scope`. |
| `Wizard::store(?string $name = null)` | `Contracts\Store` | A configured store. |
| `Wizard::extend(string $driver, Closure $callback)` | manager | Registers a custom store driver. |
| `Wizard::resolveScopeUsing(?Closure $resolver)` | manager | Resolves the visitor's scope; see [configuration]({{ site.baseurl }}/configuration#scope). |
| `Wizard::resolveUrlsUsing(?Closure $resolver)` | manager | Resolves step URLs: `fn (Wizard $wizard, Step $step): ?string`. |

Inject `Invelity\WizardPackage\Contracts\Factory` instead of using the facade if you prefer contracts.

## A wizard instance

### State

| Method | Returns | Description |
| --- | --- | --- |
| `name()` | `string` | The wizard's name. |
| `steps()` | `StepCollection` | The steps, in order. |
| `step(string $id)` | `Contracts\Step` | A step; throws `StepNotFoundException`. |
| `scope()` | `string` | The visitor's scope. |
| `state()` | `State` | The immutable state: `current`, `completed`, `skipped`, `data`, `metadata`, `startedAt`, `completedAt`, `attributes`. |
| `exists()` | `bool` | Whether anything is stored. |
| `isCompleted()` | `bool` | Whether `complete()` succeeded. |
| `data(?string $step = null)` | `array` | The data of every step, or of one step. |
| `metadata(?string $key = null, mixed $default = null)` | `mixed` | All metadata, or one value using dot notation. |
| `progress()` | `Progress` | `completed`, `total`, `percentage()`, `remaining()`. |

### Flow

| Method | Returns | Description |
| --- | --- | --- |
| `current()` | `?Step` | The step the visitor should be on. |
| `next(?string $step = null)` | `?Step` | The step after the given step, or after the current one. |
| `previous(?string $step = null)` | `?Step` | The step before the given step, or before the current one. |
| `firstUnfinished()` | `?Step` | The first step that still needs input. |
| `canAccess(string $step)` | `bool` | Whether the visitor may open the step. |
| `navigation(?string $step = null)` | `Navigation` | The navigation as seen from a step: `items`, `current`, `previous`, `next`, `canGoBack()`, `canGoForward()`, `item($id)`. |
| `url(string $step)` | `?string` | The step's URL, if the application provides one. |

### Changes

| Method | Returns | Description |
| --- | --- | --- |
| `start(array $metadata = [])` | `static` | Stores the wizard with initial metadata, unless it exists. |
| `process(string $step, Request\|array $input = [])` | `array` | Validates and stores a step, reopens its dependents if the data changed, and moves on. |
| `skip(string $step)` | `void` | Skips an optional step and moves on. |
| `reopen(string $step)` | `void` | Reopens a step and its dependents and moves the visitor there. |
| `goTo(string $step)` | `void` | Moves the visitor to an accessible step. |
| `putMetadata(string\|array $key, mixed $value = null)` | `void` | Stores metadata using dot notation. |
| `forgetMetadata(string\|array $keys)` | `void` | Removes metadata. |
| `complete()` | `array` | Completes the wizard and returns the data of every step. |
| `reset()` | `void` | Removes the stored state. |

## Navigation items

```php
foreach ($wizard->navigation('summary')->items as $item) {
    $item->id;            // "calculator"
    $item->title;         // "Calculator"
    $item->position;      // 1
    $item->status;        // StepStatus::Pending | Completed | Skipped
    $item->current;       // true for the step the navigation is seen from
    $item->accessible;
    $item->optional;
    $item->displayOnly;
    $item->url;           // see "Step URLs"
}
```

Navigation and progress also implement `Arrayable` and `JsonSerializable`.

## HTTP API

The package registers no routes. Register the JSON API of a wizard where you want it:

```php
Route::middleware(['web', 'auth'])->group(function () {
    Route::wizard('order', OrderWizard::class);
});
```

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| `GET` | `order` | `order.show` | The wizard as seen from the current step. |
| `POST` | `order` | `order.complete` | Complete the wizard. |
| `DELETE` | `order` | `order.destroy` | Reset the wizard (`204`). |
| `GET` | `order/{step}` | `order.step` | The wizard as seen from a step, with the step's data. |
| `POST` | `order/{step}` | `order.process` | Process the step through its form request. |
| `POST` | `order/{step}/skip` | `order.skip` | Skip an optional step. |

How the routes are registered:

- **Names** follow the URI, like resource routes (`shops/{shop}/checkout` → `shops.checkout.*`), and pick up the group's
  prefix, name and middleware.
- **Constraints:** `{step}` only matches the wizard's steps. `process` leaves out display-only steps, and `skip` exists
  only for optional steps.
- **Session:** the session store needs the `web` middleware group.

Every endpoint returns the same shape:

```json
{
  "data": {
    "wizard": "order",
    "step": {
      "id": "personal-data",
      "title": "Personal Data",
      "is_optional": false,
      "is_display_only": false,
      "data": {}
    },
    "previous_step": "calculator",
    "next_step": "newsletter",
    "is_completed": false,
    "progress": { "completed_steps": 1, "total_steps": 5, "percentage": 20 },
    "navigation": [
      {
        "id": "calculator",
        "title": "Calculator",
        "position": 1,
        "status": "completed",
        "is_current": false,
        "is_accessible": true,
        "is_optional": false,
        "is_display_only": false,
        "url": "https://example.com/order/calculator"
      }
    ]
  }
}
```

Errors use Laravel's own responses:

| Status | When |
| --- | --- |
| `422` | Invalid input (`errors` per field), skipping a required step (`errors.step`), completing an unfinished wizard (`errors.steps`). |
| `403` | The step is not accessible yet, or the form request's `authorize()` denied it. |
| `404` | The step does not exist. |
| `409` | The wizard was already completed. |

## Middleware

For steps served from your own routes, `EnsureStepIsAccessible` redirects visitors to the step they should be on, or
answers `403` to JSON requests:

```php
use Invelity\WizardPackage\Http\Middleware\EnsureStepIsAccessible;

Route::get('/checkout/{step}', ShowCheckoutStep::class)
    ->middleware(EnsureStepIsAccessible::using(OrderWizard::class));

Route::get('/flow/{page}', ShowPage::class)
    ->middleware('wizard.step:'.OrderWizard::class.',page');   // custom route parameter
```

The default redirect is the same route with the step parameter replaced. Send visitors elsewhere with:

```php
EnsureStepIsAccessible::redirectUsing(
    fn (Request $request, Wizard $wizard, Step $step) => route('checkout.start'),
);
```

## Exceptions

| Exception | HTTP | Thrown when |
| --- | --- | --- |
| `StepNotFoundException` | 404 | A step id does not exist. Has `$step`. |
| `StepNotAccessibleException` | 403 | A step may not be opened yet. Has `$wizard`, `$step`, `$current`. |
| `WizardAlreadyCompletedException` | 409 | A completed wizard is changed. Has `$wizard`. |
| `InvalidWizardException` | 500 | A wizard or step is defined incorrectly, such as duplicate ids, unknown dependencies, or a wizard created with `new` and used directly. |
| `Illuminate\Validation\ValidationException` | 422 | Invalid input, or a rule of the flow was broken. |

## Commands

| Command | Description |
| --- | --- |
| `wizard:make {name} [--force]` | Create a wizard in `App\Wizards`. |
| `wizard:make-step {name} [--wizard=] [--optional] [--display] [--view[=]] [--force]` | Create a step in `App\Wizards\Steps`, its form request in `App\Http\Requests\Wizards`, and add the step to a wizard. `--view` also creates its Blade view with `make:view`, named `{wizard}.{step}` unless a name is given. |
| `wizard:prune [--store=] [--days=30]` | Remove states that have not changed for the given number of days. |
