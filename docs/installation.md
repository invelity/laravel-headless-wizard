---
layout: default
title: Installation
nav_order: 2
---

# Installation

Get started with Laravel Headless Wizard in just a few minutes.

---

## Requirements

- PHP 8.4 or higher
- Laravel 11.0 or 12.0

---

## Step 1: Install via Composer

```bash
composer require invelity/laravel-headless-wizard
```

---

## Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag="wizard-config"
```

This creates `config/wizard.php` where you can configure storage, routes, and behavior.

---

## Step 3: Publish Migrations (Optional)

If you want to use database storage instead of session:

```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

---

## Step 4: Publish Assets (Optional)

### Blade Components

Publish Blade components for customization:

```bash
php artisan vendor:publish --tag="wizard-components"
```

Components will be published to `resources/views/vendor/wizard-package/components/`.

### Vue 3 Composable

Publish Vue composable and TypeScript definitions:

```bash
php artisan vendor:publish --tag="wizard-assets"
```

Assets will be published to `resources/js/composables/` and `resources/js/types/`.

### Command Stubs

Publish command stubs for customization:

```bash
php artisan vendor:publish --tag="wizard-stubs"
```

Stubs will be published to `stubs/vendor/wizard/`.

---

## Verify Installation

Create your first wizard to verify everything is working:

```bash
php artisan wizard:make Onboarding
```

**Interactive prompts:**
```
 What type of wizard do you want to create?
  [blade] Blade (Traditional server-side rendering)
  [api] API (Headless JSON responses)
  [livewire] Livewire (Reactive components)
  [inertia] Inertia.js (SPA with Vue/React)
 > blade

ℹ Wizard created successfully!
✎ Wizard class: app/Wizards/OnboardingWizard/Onboarding.php
✎ Controller: app/Http/Controllers/OnboardingController.php
✎ Views: resources/views/wizards/onboarding/

✎ Next steps:
  • Generate first step: php artisan wizard:make-step Onboarding
  • Wizard will be auto-discovered on next request
```

For API/SPA wizards, you'll also see:
```
⚠ CSRF Protection Notice
✎ For API/SPA wizards, add wizard routes to CSRF exceptions:
✎ app/Http/Middleware/VerifyCsrfToken.php
✎ protected $except = ['api/wizards/onboarding/*'];
```

---

## Quick Setup Guide

For a complete step-by-step guide with Blade and Vue examples, see the [Setup Guide](https://github.com/invelity/laravel-headless-wizard/blob/main/SETUP.md) in the demo repository.

This includes:
- Complete wizard structure setup
- Blade implementation with views
- Vue.js SPA implementation
- CSRF configuration
- Environment setup
- Troubleshooting

---

## Next Steps

- [Configure your wizard](configuration)
- [Create wizard steps](creating-wizards)
- [View examples](examples)
