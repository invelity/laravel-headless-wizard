---
layout: default
title: Home
nav_order: 1
---

# Laravel Headless Wizard

![Laravel Headless Wizard]({{ site.baseurl }}/assets/images/featured.png)

Multi-step wizards for Laravel, built the way Laravel itself is built.

- **Declare** a wizard and its steps as classes.
- **Validate** every step with a form request.
- **Let the package keep** each visitor's progress and work out where they may go next.
- **Render** it any way you like: Blade, Livewire, Inertia, Vue, React or a native app.

```php
use App\Wizards\OrderWizard;

class OrderStepController
{
    public function store(Request $request, OrderWizard $wizard, string $step)
    {
        $wizard->process($step, $request);   // validated by the step's form request

        return to_route('order.step', $wizard->current()->id());
    }
}
```

## Why this package

- **Typed and stateless.** The wizard is injected like a form request and bound to the current visitor. There is no
  `initialize()` and no string ids.
- **Laravel-native validation.** Each step names a form request, and the request runs its whole lifecycle.
- **Flow rules in one place.**
  - optional and conditional steps;
  - dependencies that reopen later steps when earlier data changes;
  - display-only confirmation steps.
- **Safe by default.**
  - every visitor's state is kept apart;
  - only validated input is stored;
  - the package registers no routes unless you ask for them.
- **Extensible.** Custom stores plug in through `Wizard::extend()`, and every lifecycle moment dispatches an event.

## Requirements

- PHP 8.4 or higher
- Laravel 12 or 13

## Five-minute tour

```bash
composer require invelity/laravel-headless-wizard

php artisan wizard:make OrderWizard
php artisan wizard:make-step CalculatorStep --wizard=OrderWizard
php artisan wizard:make-step PersonalDataStep --wizard=OrderWizard
php artisan wizard:make-step ConfirmationStep --wizard=OrderWizard --display
```

Then:

1. add rules to `app/Http/Requests/Wizards/CalculatorRequest.php` and `PersonalDataRequest.php`;
2. serve the steps from your own controllers, or register the [JSON API]({{ site.baseurl }}/api-reference#http-api);
3. read [Wizards and steps]({{ site.baseurl }}/creating-wizards) to learn optional, conditional and dependent steps.

Upgrading from 1.x? See the [upgrade guide]({{ site.baseurl }}/upgrading).
