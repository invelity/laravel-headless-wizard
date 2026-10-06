---
layout: default
title: Testing
nav_order: 7
---

# Testing

## Use the array store

Keep wizard state in memory during tests:

```php
// tests/TestCase.php or a Pest beforeEach()
config(['wizard.default' => 'array']);
```

The session store works in tests too: Laravel's array session driver keeps the state for the duration of a test.

## Test a flow through the wizard API

```php
use App\Wizards\OrderWizard;
use Invelity\WizardPackage\Facades\Wizard;

it('quotes the price of a parcel', function () {
    $wizard = Wizard::for(OrderWizard::class);

    $data = $wizard->process('calculator', ['weight' => '2']);

    expect($data['price'])->toBe(9.0)
        ->and($wizard->current()->id())->toBe('personal-data');
});

it('refuses the summary before the calculator', function () {
    expect(Wizard::for(OrderWizard::class)->canAccess('summary'))->toBeFalse();
});
```

Validation failures throw Laravel's `ValidationException`:

```php
it('requires a weight', function () {
    Wizard::for(OrderWizard::class)->process('calculator', []);
})->throws(Illuminate\Validation\ValidationException::class);
```

## Test your controllers

```php
it('moves to the next step', function () {
    $this->post('/order/calculator', ['weight' => '2'])
        ->assertRedirect('/order/personal-data');
});

it('keeps visitors on the steps they may open', function () {
    $this->get('/order/summary')->assertRedirect('/order/calculator');
});
```

## Arrange a wizard in a given state

Drive the wizard instead of writing raw state. That way the arrangement goes through the same rules as production:

```php
beforeEach(function () {
    $this->wizard = Wizard::for(OrderWizard::class);
    $this->wizard->process('calculator', ['weight' => '2']);
    $this->wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);
});
```

Wizards of other visitors, in tests for jobs or admin screens:

```php
$wizard = Wizard::for(OrderWizard::class, $user);
```

## Assert events

```php
use Illuminate\Support\Facades\Event;
use Invelity\WizardPackage\Events\WizardCompleted;

Event::fake([WizardCompleted::class]);

// ... complete the wizard ...

Event::assertDispatched(fn (WizardCompleted $event) => $event->wizard === OrderWizard::class);
```
