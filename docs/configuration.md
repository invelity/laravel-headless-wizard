---
layout: default
title: Configuration
nav_order: 3
---

# Configuration

Learn how to configure Laravel Headless Wizard for your application.

---

## Configuration File

After publishing the config, you'll find `config/wizard-package.php` with the following options:

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
        'fire_events' => true, // Fire lifecycle events
    ],

    'wizards' => [
        // Your wizards will be registered here automatically
    ],
];
```

---

## Storage Drivers

### Session Storage (Default)

Stores wizard data in the user's session. Best for simple wizards.

```php
'storage' => [
    'driver' => 'session',
],
```

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

```php
'storage' => [
    'driver' => 'database',
],
```

**Pros:**
- Persistent across sessions
- Can resume on different devices
- Queryable for analytics

**Cons:**
- Requires database table
- Slightly slower than session/cache

**Setup:**
```bash
php artisan vendor:publish --tag="wizard-migrations"
php artisan migrate
```

### Cache Storage

Stores wizard data in your cache driver. Best for high-performance needs.

```php
'storage' => [
    'driver' => 'cache',
    'ttl' => 3600, // Time to live in seconds
],
```

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

```php
'navigation' => [
    'allow_jump' => true,
],
```

### Show All Steps

Control whether all steps are visible in navigation:

```php
'navigation' => [
    'show_all_steps' => false, // Only show accessible steps
],
```

---

## Validation Options

### Validate on Navigate Back

Require validation when navigating to previous steps:

```php
'validation' => [
    'validate_on_navigate' => true,
],
```

### Mark as Completed

Automatically mark steps as completed after successful validation:

```php
'validation' => [
    'marks_completed' => true,
],
```

---

## Route Configuration

### Custom Prefix

Change the URL prefix for wizard routes:

```php
'routes' => [
    'prefix' => 'my-wizard', // /my-wizard/checkout/step-1
],
```

### Middleware

Add middleware to wizard routes:

```php
'routes' => [
    'middleware' => ['web', 'auth', 'verified'],
],
```

### Disable Routes

If you want to handle routing yourself:

```php
'routes' => [
    'enabled' => false,
],
```

---

## Events

### Enable/Disable Events

Control whether lifecycle events are fired:

```php
'events' => [
    'fire_events' => false, // Disable all events
],
```

Available events:
- `WizardStarted`
- `StepCompleted`
- `StepSkipped`
- `WizardCompleted`

---

## Environment-Specific Configuration

You can override configuration in your `.env` file:

```env
WIZARD_STORAGE_DRIVER=database
WIZARD_ALLOW_JUMP_NAVIGATION=true
WIZARD_FIRE_EVENTS=false
```

Then reference in config:

```php
'storage' => [
    'driver' => env('WIZARD_STORAGE_DRIVER', 'session'),
],
```

---

## Next Steps

- [Create your first wizard](creating-wizards)
- [View API reference](api-reference)
- [See examples](examples)
