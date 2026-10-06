---
layout: default
title: Upgrading
nav_order: 8
---

# Upgrading

## From 1.x to 2.0

2.0 rebuilds the package the way Laravel itself is built:

- **wizards and steps** are classes;
- **step input** is validated by form requests with their whole lifecycle;
- **state** is kept per visitor in configurable stores;
- **routes** are opt-in.

Sessions written by 1.x keep working, so visitors in the middle of a wizard keep their progress through the deploy.

The complete guide maps every 1.x class, method, config key and event to its replacement:
**[UPGRADING.md](https://github.com/invelity/laravel-headless-wizard/blob/main/UPGRADING.md)**.

The short version:

1. `composer require "invelity/laravel-headless-wizard:^2.0"`. This needs PHP 8.4+ and Laravel 12 or 13.
2. Republish `config/wizard.php`; `storage.driver` became `default`.
3. Turn each wizard into a class that extends `Invelity\WizardPackage\Wizard` and lists its `$steps`. Keep the 1.x id in
   `$name` (for example `order-wizard`) so the session key stays the same.
4. Turn each step's constructor arguments into properties, `getFormRequest()` into `$formRequest`, and `process()` into
   an optional `handle()`.
5. Replace `initialize()` + `processStep()` with an injected wizard and `process()`; raw state writes become
   `putMetadata()`, `goTo()` and `reopen()`.
6. Register routes with `Route::wizard()` if you used the package routes, and use `EnsureStepIsAccessible` with your own
   routes.
