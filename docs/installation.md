---
layout: default
title: Installation
nav_order: 2
---

# Installation

## Requirements

- PHP 8.4 or higher
- Laravel 12 or 13

## Install the package

```bash
composer require invelity/laravel-headless-wizard
```

The service provider and the `Wizard` facade are discovered automatically.

## Choose a store

The state of a wizard lives in the **session** by default, so there is nothing else to set up. Pick another store when
the wizard must outlive the session or follow a user across devices.

| Store | Use it when | Setup |
| --- | --- | --- |
| `session` | progress belongs to one browser session (default) | none |
| `cache` | many servers share a cache and sessions are short | `WIZARD_STORE=cache`, optionally `WIZARD_CACHE_STORE=redis` |
| `database` | progress must survive for days, or you want to query or prune it | publish and run the migration, `WIZARD_STORE=database` |
| `array` | tests | `config(['wizard.default' => 'array'])` |

For the database store:

```bash
php artisan vendor:publish --tag=wizard-migrations
php artisan migrate
```

Schedule the removal of abandoned states:

```php
// routes/console.php
Schedule::command('wizard:prune --days=30')->daily();
```

## Publish what you want to change

```bash
php artisan vendor:publish --tag=wizard-config        # config/wizard.php
php artisan vendor:publish --tag=wizard-translations  # lang/vendor/wizard
php artisan vendor:publish --tag=wizard-stubs         # stubs/*.stub used by the generators
```

`php artisan about` shows the installed version and the default store.
