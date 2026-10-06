# Contributing

Contributions are welcome. Please read this guide before you open a pull request.

## Before you start

- Look for an existing issue first. If there is none, open one before you start larger changes, so that the design can
  be agreed before code is written.
- Report security vulnerabilities privately, as described in [SECURITY.md](SECURITY.md). Never use public issues for them.

## Development setup

The package is developed against [Orchestra Testbench](https://packages.tools/testbench); no Laravel application is
needed.

```bash
git clone git@github.com:invelity/laravel-headless-wizard.git
cd laravel-headless-wizard
composer install
```

The test suite needs PHP 8.4+ with the `pdo_sqlite` extension.

| Task | Command |
| --- | --- |
| Run the tests | `composer test` |
| Run one test file | `vendor/bin/pest tests/Unit/StateTest.php` |
| Static analysis | `composer analyse` |
| Fix the code style | `composer format` |
| Check the code style | `vendor/bin/pint --test` |

## Pull requests

- Branch from `main` for new work, or from `1.x` for fixes to the 1.x line.
- One pull request per issue; reference it in the description (`Closes #123`).
- The pull request title becomes the squash commit message, so use
  [Conventional Commits](https://www.conventionalcommits.org/): `feat: …`, `fix: …`, `refactor: …`, `docs: …`,
  `test: …`, `chore: …`. Mark breaking changes with `!` (`feat!: …`).
- Add tests for every behaviour change; bug fixes start with a failing test.
- Add a line to the `## Unreleased` section of [CHANGELOG.md](CHANGELOG.md) for user-facing changes.
- The `tests`, `phpstan` and `pint` checks must pass before a pull request can be merged.

## Coding standards

- Follow the conventions of the Laravel framework itself: contracts for extension points, small final classes,
  constructor injection of `Illuminate\Contracts\*` instead of facades or helpers inside the package.
- `declare(strict_types=1);` in every PHP file, full native types, generics in docblocks where PHPStan needs them.
- Keep the public API documented in the docs site.
