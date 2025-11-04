# Laravel Headless Wizard

![Laravel Headless Wizard](featured.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/invelity/laravel-headless-wizard.svg?style=flat-square)](https://packagist.org/packages/invelity/laravel-headless-wizard)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/invelity/laravel-headless-wizard/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/invelity/laravel-headless-wizard/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/invelity/laravel-headless-wizard.svg?style=flat-square)](https://packagist.org/packages/invelity/laravel-headless-wizard)
![Code Coverage](https://img.shields.io/badge/coverage-98.6%25-brightgreen?style=flat-square)
![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?style=flat-square)

A powerful **headless** multi-step wizard package for Laravel. Build complex, multi-page forms with progress tracking, navigation, and validation. **Bring your own frontend** - works with React, Vue, Inertia, Livewire, Alpine.js, or any JavaScript framework.

## ✨ Features

- 🚀 **Zero Frontend Lock-in** - Pure JSON API for any framework
- ⚡ **Interactive Generators** - Beautiful CLI with Laravel Prompts
- ✅ **Laravel-Native Validation** - Uses FormRequest classes
- 💾 **Flexible Storage** - Session, database, or cache
- 📊 **Smart Progress Tracking** - Real-time completion percentages
- 🔀 **Conditional Logic** - Optional steps and dynamic flows
- 🔔 **Event-Driven** - Hook into every wizard lifecycle event
- ✨ **Modern PHP 8.4** - Property hooks and strict types

## 📚 Documentation

**Full documentation available at: [https://invelity.github.io/laravel-headless-wizard/](https://invelity.github.io/laravel-headless-wizard/)**

- [Installation Guide](https://invelity.github.io/laravel-headless-wizard/installation)
- [Configuration](https://invelity.github.io/laravel-headless-wizard/configuration)
- [Creating Wizards](https://invelity.github.io/laravel-headless-wizard/creating-wizards)
- [API Reference](https://invelity.github.io/laravel-headless-wizard/api-reference)
- [Examples](https://invelity.github.io/laravel-headless-wizard/examples)
- [Testing Guide](https://invelity.github.io/laravel-headless-wizard/testing)

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

Generate wizard steps:

```bash
php artisan wizard:make-step --wizard=onboarding
```

## 📋 Requirements

- PHP 8.4 or higher
- Laravel 11.0 or 12.0

## 📊 Code Quality

- **98.6% Test Coverage** - 375 comprehensive Pest tests
- **Cyclomatic Complexity: 4.37** - Clean, maintainable code
- **PHPStan Level 5** - Zero static analysis errors
- **100% Type Coverage** - Full type declarations

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🔒 Security

Report security vulnerabilities via [GitHub Security](../../security/policy).

## 📝 License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.

## 👨‍💻 Credits

- [Martin Halaj](https://github.com/Martin-1182)
- [All Contributors](../../contributors)

---

Built with ❤️ by [Invelity](https://github.com/invelity)
