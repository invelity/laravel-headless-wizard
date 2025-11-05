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

## Verify Installation

Create your first wizard to verify everything is working:

```bash
php artisan wizard:make Onboarding
```

You should see:
```
✓ Wizard class created: app/Wizards/Onboarding.php
✓ Registered in config: config/wizard.php
✓ Config cache cleared

Next steps:
  • Generate first step: php artisan wizard:make-step --wizard=onboarding
  • View wizard config: config/wizard.php
```

---

## Next Steps

- [Configure your wizard](configuration)
- [Create wizard steps](creating-wizards)
- [View examples](examples)
