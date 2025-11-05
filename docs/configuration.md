---
layout: default
title: Configuration
nav_order: 3
---

# Configuration

Learn how to configure Laravel Headless Wizard for your application.

---

## Configuration File

After publishing the config, you'll find `config/wizard.php` with the following options:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
return [
    'storage' => [
        'driver' => 'session', // session, database, or cache
        'ttl' => 3600, // Cache TTL in seconds
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'wizard',
        'middleware' => ['web'],
    ],

    'navigation' => [
        'allow_jump' => false, // Allow jumping to any completed step
        'show_all_steps' => true, // Show all steps in navigation
    ],

    'validation' => [
        'validate_on_navigate' => true, // Validate when navigating back
        'marks_completed' => true, // Mark step as completed after validation
    ],

    'events' => [
        'dispatch' => true, // Fire lifecycle events
        'log_progress' => false,
    ],

    'cleanup' => [
        'abandoned_after_days' => 30,
        'auto_cleanup' => false, // Enable scheduled cleanup
    ],
];
```

</div>

**Note:** Wizards and steps are **auto-discovered** from `app/Wizards/*Wizard/` directories. No manual registration needed!

---

## Storage Drivers

### Session Storage (Default)

Stores wizard data in the user's session. Best for simple wizards.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'storage' => [
    'driver' => 'session',
],
```

</div>

**Important:** Ensure your `.env` uses a persistent session driver:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```env
SESSION_DRIVER=file  # or database, redis
# DO NOT use 'array' - state will be lost between requests
```

</div>

**Pros:**
- No database setup required
- Fast access
- Automatic cleanup on session end

**Cons:**
- Data lost when session expires
- Not suitable for long-running wizards
- Can't resume across devices

### Database Storage

Stores wizard data in the database. Best for persistent wizards.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'storage' => [
    'driver' => 'database',
],
```

</div>

**Pros:**
- Persistent across sessions
- Can resume on different devices
- Queryable for analytics

**Cons:**
- Requires database table
- Slightly slower than session/cache

**Setup:**
<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

</div>

{: .important }
> **Security Note:** Step data in the `wizard_progress` table is automatically **encrypted** using Laravel's `encrypted:array` cast with your `APP_KEY`. This protects sensitive user data while the wizard is in progress. Data is automatically decrypted when retrieved. The `step_data` column uses `TEXT` type (not `JSON`) to store the encrypted string.
>
> **Important for Production:**
> - Keep your `APP_KEY` secure and backed up
> - If you rotate `APP_KEY`, existing wizard progress will become unreadable
> - Consider clearing old wizard progress before key rotation
> - For guest users, wizard data is tied to session - no `user_id` foreign key constraint

### Cache Storage

Stores wizard data in your cache driver. Best for high-performance needs.

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'storage' => [
    'driver' => 'cache',
    'ttl' => 3600, // Time to live in seconds
],
```

</div>

**Pros:**
- Very fast (especially with Redis/Memcached)
- Automatic expiration
- Scales horizontally

**Cons:**
- May expire unexpectedly
- Not queryable
- Requires cache setup

---

## Navigation Options

### Allow Jump Navigation

Allow users to jump to any completed step:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'navigation' => [
    'allow_jump' => true,
],
```

</div>

### Show All Steps

Control whether all steps are visible in navigation:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'navigation' => [
    'show_all_steps' => false, // Only show accessible steps
],
```

</div>

---

## Validation Options

### Validate on Navigate Back

Require validation when navigating to previous steps:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'validation' => [
    'validate_on_navigate' => true,
],
```

</div>

### Mark as Completed

Automatically mark steps as completed after successful validation:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'validation' => [
    'marks_completed' => true,
],
```

</div>

---

## Route Configuration

### Custom Prefix

Change the URL prefix for wizard routes:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'routes' => [
    'prefix' => 'my-wizard', // /my-wizard/checkout/step-1
],
```

</div>

### Middleware

Add middleware to wizard routes:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'routes' => [
    'middleware' => ['web', 'auth', 'verified'],
],
```

</div>

### Disable Routes

If you want to handle routing yourself:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'routes' => [
    'enabled' => false,
],
```

</div>

---

## Events

### Enable/Disable Events

Control whether lifecycle events are fired:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'events' => [
    'dispatch' => false, // Disable all events
    'log_progress' => true, // Log wizard progress
],
```

</div>

Available events:
- `WizardStarted`
- `StepCompleted`
- `StepSkipped`
- `WizardCompleted`

---

## Environment-Specific Configuration

You can override configuration in your `.env` file:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```env
WIZARD_STORAGE_DRIVER=database
WIZARD_ALLOW_JUMP_NAVIGATION=true
WIZARD_FIRE_EVENTS=false
```

</div>

Then reference in config:

<div style="background: #272B33; border-radius: 0.75rem; overflow: hidden; margin: 1.5rem 0;">

```php
'storage' => [
    'driver' => env('WIZARD_STORAGE_DRIVER', 'session'),
],
```

</div>

---

## Next Steps

- [Create your first wizard](creating-wizards)
- [View API reference](api-reference)
- [See examples](examples)
