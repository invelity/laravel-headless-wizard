# Changelog

All notable changes to `wizard-package` will be documented in this file.

## v1.3.1 - Complete SOLID Refactoring with Documentation - 2025-11-17

### 🐛 Bug Fixes

- Fixed missing CHANGELOG.md documentation in v1.3.0 release
- Removed temporary SOLID_AUDIT_REPORT.md file

### 📚 Documentation

- Added complete SOLID refactoring documentation to CHANGELOG.md
- All v1.3.0 changes now properly documented

**Full Changelog**: https://github.com/invelity/laravel-headless-wizard/compare/v1.3.0...v1.3.1

## v1.3.0 - SOLID Refactoring & Quality Improvements - 2025-11-14

### 🎉 Major Improvements

This release brings comprehensive SOLID refactoring with significant improvements to code quality, test coverage, and maintainability.

### ✨ Features

#### SOLID Architecture Refactoring

- **Single Responsibility Principle**: Extracted specialized services from WizardManager
- **Interface Segregation Principle**: Split WizardManager into 4 focused interfaces
- **Dependency Inversion Principle**: Created abstractions for all dependencies
- **Open/Closed Principle**: Enhanced extensibility through better abstractions
- **Don't Repeat Yourself**: Removed code duplication across the package

#### New Services

- `WizardLifecycleManager` - Handles wizard initialization, completion, and reset
- `WizardStepProcessor` - Processes and validates wizard steps
- `WizardEventManager` - Manages wizard events
- `WizardProgressTracker` - Tracks wizard completion progress
- `StepFinderService` - Finds steps by ID and index
- `WizardNavigationFactory` - Creates navigation instances

#### New Components

- `StepGenerator` - Generates step class files
- `FormRequestGenerator` - Generates FormRequest files
- `WizardStepResponseBuilder` - Builds standardized responses

#### New Interfaces

- `WizardInitializationInterface` - Wizard initialization contract
- `WizardDataInterface` - Data management contract
- `WizardStepAccessInterface` - Step access control contract
- `WizardNavigationManagerInterface` - Navigation management contract
- `WizardEventManagerInterface` - Event management contract
- `StepFinderInterface` - Step finding contract

### 🐛 Bug Fixes

- Fixed command registration to support constructor dependency injection
- Fixed Laravel Prompts testing compatibility
- Fixed FormRequest stub file path
- Fixed CacheStorageTest with array cache driver
- Fixed WizardSessionMiddlewareTest with ArraySessionHandler

### 📊 Quality Metrics

#### Test Coverage

- **398/398 tests passing** (100%)
- **89.4% line coverage** (1016/1136 lines)
- **88.3% method coverage** (204/231 methods)
- **11 out of 18 directories** have 100% coverage

#### Static Analysis

- **PHPStan**: Level max, 0 errors
- **ArchTest**: 21/21 tests passing (100% SOLID compliant)
- **Dead Code**: 0 unused methods found

#### Coverage Highlights

- Core business logic: 98.95%
- HTTP layer: 100%
- Storage layer: 100%
- Actions: 97.14%

### 📈 Code Quality Improvements

- Reduced WizardManager from 262 to 213 lines (19% reduction)
- Extracted 6 new specialized service classes
- Created 6 new focused interfaces
- Improved testability through dependency injection
- Enhanced code organization and maintainability

### 🔄 Breaking Changes

**None** - All changes are internal refactoring. The public API remains fully backward compatible.

### 📚 Documentation

- Added comprehensive SOLID audit report
- Documented all new services and interfaces
- Updated code coverage reports

### 🙏 Credits

This release represents a significant improvement in code quality and architecture, ensuring the package is maintainable, testable, and follows industry best practices.


---

**Full Changelog**: https://github.com/invelity/laravel-headless-wizard/compare/v1.2.0...v1.3.0

## [1.2.0] - 2025-11-07

### Added

- **Interactive Wizard Generation**: Laravel Prompts integration for beautiful CLI experience
  
  - Interactive wizard type selection (Blade, API, Livewire, Inertia)
  - Step-by-step guided wizard and step creation with validation hints
  - Rich output with `info()`, `note()`, and `warning()` helpers
  - CSRF protection warnings for API/SPA wizards
  
- **Blade Components**: Pre-built UI components for rapid prototyping
  
  - `<x-wizard::layout>` - Base wizard layout with title support
  - `<x-wizard::progress-bar>` - Automatic progress calculation and display
  - `<x-wizard::step-navigation>` - Customizable back/next/complete buttons
  - `<x-wizard::form-wrapper>` - Form with CSRF and error handling
  - All components are publishable and customizable
  
- **Vue 3 Composable**: `useWizard()` for SPA integration
  
  - Full TypeScript definitions included
  - Reactive state management with Vue 3 Composition API
  - Automatic API communication with CSRF token handling
  - Form helpers: `setFieldValue()`, `getFieldError()`, `clearErrors()`
  - Navigation: `submitStep()`, `goToStep()`, `initialize()`
  
- **Automatic Step Reordering**: Insert steps at any position, existing steps automatically renumbered
  
- **Smart Default Value Omission**: Generated step classes only include non-default parameters (cleaner code)
  
- **WizardDiscoveryService**: Automatic wizard and step discovery from `app/Wizards/` directory
  

### Improved

- Commands now use Laravel Prompts for all user interactions
- Generated views use package components (building blocks approach)
- Step stubs omit `isOptional: false` and `canSkip: false` for cleaner generated code
- Better error messages with troubleshooting hints
- Enhanced command output with file paths and next steps

### Fixed

- All stubs now have `declare(strict_types=1)` for strict type checking
- Constructor property promotion used throughout generated code
- Named arguments in all parent constructor calls
- Architecture tests exclude Components from God object and View return checks

### Developer Experience

- 397 comprehensive tests (89.7% coverage)
- PHPStan Level 5 with zero errors
- Laravel Pint compliance (126 files formatted)
- Architecture tests enforce SOLID principles
- Generated code passes PHPStan Level 5

**Author:** Martin-1182 [halaj@invelity.com](mailto:halaj@invelity.com)

## [1.1.0] - 2025-11-05

### Bug Fixes

- Fixed database storage support for guest users (removed foreign key constraint)
- Fixed encrypted step_data storage (changed from json to text column type)
- Fixed migration to make current_step_id nullable
- Fixed WizardServiceProvider to handle array config format for storage driver

### Documentation

- Added comprehensive security documentation for encrypted step data
- Added wizard progress status documentation (in_progress, completed, abandoned)
- Documented all wizard lifecycle events (WizardStarted, StepCompleted, StepSkipped, WizardCompleted)
- Added cleanup command examples for abandoned wizards
- Improved database storage configuration guide with production considerations

### Improvements

- Enhanced error handling for guest user workflows
- Better MariaDB compatibility for encrypted data storage

**Author:** Martin-1182 [halaj@invelity.com](mailto:halaj@invelity.com)

## [1.0.0] - 2025-11-04

### Initial Release

**Complete headless/API-first wizard package for Laravel.**

This release removes all view layer dependencies and implements a frontend-agnostic JSON API. Migration from v1.x requires significant changes. See [Migration Guide](markdowns/migration-v1-to-v2.md).

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

See complete migration guide: [docs/migration-v1-to-v2.md](markdowns/migration-v1-to-v2.md)

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
