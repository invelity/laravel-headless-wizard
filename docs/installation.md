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

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```bash
composer require invelity/laravel-headless-wizard
```

</div>

---

## Step 2: Publish Configuration

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```bash
php artisan vendor:publish --tag="wizard-config"
```

</div>

This creates `config/wizard.php` where you can configure storage, routes, and behavior.

---

## Step 3: Publish Migrations (Optional)

If you want to use database storage instead of session:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

</div>

---

## Verify Installation

Create your first wizard to verify everything is working:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```bash
php artisan wizard:make Onboarding
```

</div>

You should see:
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```
✓ Wizard class created: app/Wizards/OnboardingWizard/Onboarding.php
✓ Wizard directory created: app/Wizards/OnboardingWizard/
✓ Wizard will be auto-discovered on next request

Next steps:
  • Generate first step: php artisan wizard:make-step Onboarding
  • Wizard will be automatically discovered - no config needed!
```

</div>

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
