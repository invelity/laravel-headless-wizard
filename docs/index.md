---
layout: default
title: Home
nav_order: 1
---

# Laravel Multi-Step Wizard Package (Headless)

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-6" markdown="0">
  <a href="https://packagist.org/packages/invelity/laravel-headless-wizard" target="_blank" rel="noopener" class="flex flex-col items-center justify-center px-4 py-3 bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-lg hover:shadow-md transition-all duration-200 group no-underline">
    <span class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Version</span>
    <span class="text-lg font-bold text-primary-600 group-hover:text-primary-700">v1.0.0</span>
  </a>
  <a href="https://github.com/invelity/laravel-headless-wizard/actions" target="_blank" rel="noopener" class="flex flex-col items-center justify-center px-4 py-3 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg hover:shadow-md transition-all duration-200 group no-underline">
    <span class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Tests</span>
    <span class="text-lg font-bold text-green-600 group-hover:text-green-700">Passing</span>
  </a>
  <a href="https://github.com/invelity/laravel-headless-wizard" target="_blank" rel="noopener" class="flex flex-col items-center justify-center px-4 py-3 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg hover:shadow-md transition-all duration-200 group no-underline">
    <span class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Coverage</span>
    <span class="text-lg font-bold text-green-600 group-hover:text-green-700">98.6%</span>
  </a>
  <a href="https://github.com/invelity/laravel-headless-wizard" target="_blank" rel="noopener" class="flex flex-col items-center justify-center px-4 py-3 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg hover:shadow-md transition-all duration-200 group no-underline">
    <span class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">PHPStan</span>
    <span class="text-lg font-bold text-green-600 group-hover:text-green-700">Level 5</span>
  </a>
</div>

A powerful **headless** multi-step wizard package for Laravel applications. Build complex, multi-page forms with progress tracking, navigation, validation, and conditional steps. **Bring your own frontend** - works with React, Vue, Inertia, Livewire, Alpine.js, or any JavaScript framework.

---

## 🚀 Quick Start

Install the package:

```bash
composer require invelity/laravel-headless-wizard
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="wizard-config"
```

Create your first wizard:

```bash
php artisan wizard:make Onboarding
```

---

## 📚 Documentation

<div class="docs-grid" markdown="1">

### [Installation](installation)
Get started with Laravel Headless Wizard in minutes

### [Configuration](configuration)
Configure storage, routes, and behavior

### [Creating Wizards](creating-wizards)
Learn how to create multi-step wizards

### [API Reference](api-reference)
Complete API documentation

### [Examples](examples)
Real-world usage examples

### [Testing](testing)
Test your wizard implementations

</div>

---

## ✨ Key Features

- 🚀 **Zero Frontend Lock-in** - Pure JSON API for any framework
- ⚡ **Interactive Generators** - Beautiful CLI with Laravel Prompts
- ✅ **Laravel-Native Validation** - Uses FormRequest classes
- 💾 **Flexible Storage** - Session, database, or cache
- 📊 **Smart Progress Tracking** - Real-time completion percentages
- 🔀 **Conditional Logic** - Optional steps and dynamic flows
- 🔔 **Event-Driven** - Hook into every wizard lifecycle event
- ✨ **Modern PHP 8.4** - Property hooks and strict types

---

## 📊 Code Quality

- **98.6% Test Coverage** - 375 comprehensive Pest tests
- **Cyclomatic Complexity: 4.37** - Clean, maintainable code
- **PHPStan Level 5** - Zero static analysis errors
- **100% Type Coverage** - Full type declarations

---

## 🤝 Contributing

Contributions are welcome! Please see our [Contributing Guide](contributing) for details.

---

## 📝 License

The MIT License (MIT). Please see [License File](https://github.com/invelity/laravel-headless-wizard/blob/main/LICENSE.md) for more information.

---

<style>
.docs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin: 2rem 0;
}

.docs-grid h3 {
  background: #f6f8fa;
  padding: 1rem;
  border-radius: 6px;
  border-left: 3px solid #0366d6;
  margin: 0;
}

.docs-grid h3 a {
  text-decoration: none;
  color: #0366d6;
}

.docs-grid h3 a:hover {
  text-decoration: underline;
}
</style>
