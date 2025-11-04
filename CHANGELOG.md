# Changelog

All notable changes to `wizard-package` will be documented in this file.

## [2.0.0] - 2025-11-04

### ⚠️ BREAKING CHANGES

**Complete architectural rewrite from view-based to headless/API-first design.**

This release removes all view layer dependencies and implements a frontend-agnostic JSON API. Migration from v1.x requires significant changes. See [Migration Guide](docs/migration-v1-to-v2.md).

#### Removed Features
- **Views**: All Blade templates removed - package is now headless
- **Step::render()**: Removed method - steps only process data
- **Step::rules()**: Validation moved to FormRequest classes
- **View Controllers**: Controllers now return JSON only

#### Changed Features
- **Commands**: Split into `wizard:make` and `wizard:make-step` (previously single command)
- **Config Structure**: Wizards auto-registered with new structure
- **Validation**: Now handled by dedicated FormRequest classes
- **Routes**: Changed from web routes to API endpoints with JSON responses

### 🎉 New Features

#### Headless Architecture
- **API-First Design**: Complete JSON REST API for all wizard operations
- **Framework Agnostic**: Works with React, Vue, Svelte, Alpine.js, or any frontend framework
- **No View Coupling**: Zero Blade template dependencies

#### Interactive CLI with Laravel Prompts
- **`wizard:make`**: Interactive wizard generator with PascalCase validation
- **`wizard:make-step`**: Interactive step generator with wizard selection
- **Auto-Registration**: Steps and wizards automatically registered in config
- **Enhanced UX**: Helpful hints, error messages, and "next steps" guidance
- **Config Safety**: File locking and backup/rollback pattern for config updates

#### FormRequest Validation Pattern
- **Dedicated FormRequest Classes**: One FormRequest per step for validation
- **Auto-Generation**: FormRequests automatically created with steps
- **Laravel Standard**: Follows Laravel's recommended validation pattern
- **Clean Separation**: Business logic in steps, validation in FormRequests

#### Facade API
- **Static Interface**: `WizardPackage::initialize()`, `::processStep()`, etc.
- **16 Methods**: Complete API for wizard lifecycle, navigation, and data access
- **IDE Support**: Full PHPDoc annotations for auto-completion
- **Type Safe**: All methods fully type-hinted

#### Comprehensive Documentation
- **Installation Guide**: Step-by-step setup with troubleshooting
- **Quick Start**: 10-minute tutorial building complete wizard
- **Facade API Reference**: Complete method documentation with examples
- **Frontend Integration**: React, Vue, and Alpine.js integration guides
- **Migration Guide**: v1.x → v2.0 upgrade instructions
- **API Reference**: Complete REST API endpoint documentation
- **Troubleshooting**: Common issues and solutions

### 📦 API Endpoints

New JSON API endpoints (default prefix `/wizard`):

- `POST /wizard/{wizardId}/initialize` - Initialize wizard session
- `POST /wizard/{wizardId}/steps/{stepId}` - Process step with validation
- `GET /wizard/{wizardId}/state` - Get current wizard state
- `POST /wizard/{wizardId}/navigate/{stepId}` - Navigate to step
- `POST /wizard/{wizardId}/steps/{stepId}/skip` - Skip optional step
- `POST /wizard/{wizardId}/complete` - Complete wizard
- `POST /wizard/{wizardId}/reset` - Reset wizard
- `GET /wizard/{wizardId}/data` - Get all collected data
- `GET /wizard/{wizardId}/progress` - Get progress information
- `GET /wizard/{wizardId}/navigation` - Get navigation items

### 🔧 Technical Improvements

#### PHP 8.4 Features
- **Property Hooks**: Computed properties for `completionPercentage`, `label`, `icon`, `isSuccess`, `hasErrors`
- **Modern Arrays**: Using `array_find()` and `array_any()` for cleaner code

#### Code Quality
- **Tests**: 131 tests passing with full CI/CD integration
- **PHPStan**: Level 5 with zero errors
- **Laravel Pint**: All files formatted to Laravel standards
- **Architecture Tests**: Enforces SOLID principles and coding standards
- **Test Isolation**: Fixed config cache clearing for reliable test execution
- **GitHub Actions**: Multi-matrix testing (PHP 8.4, Laravel 11/12, Ubuntu/Windows)

#### Developer Experience
- **Stub Publishing**: Customize code generation templates
- **Config Validation**: PascalCase enforcement, duplicate prevention
- **Error Handling**: Comprehensive error messages with troubleshooting hints
- **Command Safety**: `--force` flag for overwriting, backup/rollback on failure

### 📚 Documentation

New documentation structure in `/docs`:
- `installation.md` - Installation and configuration
- `quickstart.md` - 10-minute tutorial
- `facade-api.md` - PHP Facade API reference
- `frontend-integration.md` - React/Vue/Alpine examples
- `migration-v1-to-v2.md` - v1.x upgrade guide
- `api-reference.md` - REST API documentation
- `troubleshooting.md` - Common issues and solutions

### 🔄 Migration from v1.x

**Required Steps:**

1. Update composer: `composer require invelity/laravel-headless-wizard:^2.0`
2. Remove old views: `rm -rf resources/views/wizards`
3. Create FormRequest for each step
4. Move validation from `Step::rules()` to FormRequest classes
5. Update controllers to return JSON instead of views
6. Build frontend UI using React/Vue/Alpine/etc
7. Update routes to use new API endpoints

See complete migration guide: [docs/migration-v1-to-v2.md](docs/migration-v1-to-v2.md)

### 📊 Package Metadata

- **Package Name**: Changed from `websystem-studio/wizard-package` to `invelity/laravel-headless-wizard`
- **Namespace**: Migrated from `WebSystemStudio\WizardPackage` to `Invelity\WizardPackage`
- **Description**: Updated to reflect headless architecture
- **Keywords**: Added `headless`, `api`, `multi-step-form`, `form-wizard`, `formrequest`
- **Requirements**: PHP 8.4+, Laravel 11.0+ or 12.0+
- **Repository**: Moved to github.com/invelity/laravel-headless-wizard

---

## [1.x] - Previous Releases

Legacy view-based architecture. See git history for v1.x changelog entries.

### Improved
- **PHP 8.4 Features**: Adopted property hooks for computed properties (isSuccess, hasErrors, label, icon, completionPercentage)
- **Modern PHP**: Replaced foreach loops with `array_find` and `array_any` for improved readability
- **Controller Architecture**: Refactored to CRUD-only pattern with single-action controllers (WizardCompletionController, WizardStepSkipController)
- **Type Safety**: Enhanced with property hook get accessors and readonly properties
- **SOLID Principles**: Added architecture tests validating SRP, ISP, and DIP compliance
- **Generator Commands**: Extracted templates to publishable stub files in `resources/stubs/`
- **Code Quality**: Cyclomatic complexity reduced from 5.03 to 4.74 (5.8% improvement)
- **Repository Cleanliness**: Comprehensive .gitignore patterns for speckit/AI tools

### New Features
- Stub publishing: `php artisan vendor:publish --tag=wizard-stubs`
- Four new focused interfaces for future v2.0 refactoring
- Architecture tests for enforcing coding standards

### Technical Debt & Future Plans (v2.0)
- **Interface Segregation**: WizardManagerInterface has 16 methods (exceeds recommended 10). Segregated interfaces created for v2.0
- **Service Extraction**: Event dispatching and persistence to be extracted in v2.0
- **Complexity Target**: Achieved 5.8% reduction (target was 20% - deferred to v2.0 for breaking changes)

### Metrics
- Tests: 65 passing (55 original + 10 new)
- PHPStan: 0 errors at level 5
- Cyclomatic Complexity: 5.03 → 4.74 (-5.8%)
- Code formatted with Laravel Pint
