# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About

`builtnoble/vite-php` is a framework-agnostic PHP library that reads Vite's `manifest.json` and outputs the correct `<script>` and `<link>` tags for both development (HMR) and production environments. It is primarily intended as a core utility for integrations with PHP templating engines (Twig, Plates, Laminas View).

## Commands

```bash
# Run tests
composer test

# Run only tests affected by recent changes
composer test:dirty

# Run tests with coverage (min 89%)
composer test:coverage

# Run mutation tests (min 70%)
composer test:mutate

# Run type coverage check (min 80%)
composer test:type-coverage

# Static analysis (PHPStan level 5)
composer analyze

# Code style fixer
vendor/bin/php-cs-fixer fix

# Rector (automated refactoring)
vendor/bin/rector process
```

To run a single test file: `vendor/bin/pest tests/Unit/ViteTest.php`

## Architecture

The library has four main source files:

- **`ViteInterface`** — the public contract. All consumers type-hint against this.
- **`Vite`** — the core implementation. Invokable (`__invoke`) — calling it with an array of entry paths returns the full HTML tag string. Handles both HMR mode (reads the hotfile to get the dev server URL) and production mode (reads `manifest.json`). Tag output order: stylesheets first, then scripts.
- **`ViteFactory::make()`** — the primary entry point for consumers. Accepts a flat `$options` array and an optional `$creator` callable for custom `Vite` subclasses. Maps option keys to setter calls on the `Vite` instance.
- **`helpers.php`** — three global functions auto-loaded by Composer: `partition()`, `randomStr()`, and `normalizeResolvers()`. These are guarded with `function_exists` checks to avoid conflicts in host applications.

### Manifest path convention

Production manifests are resolved at: `{publicPath}/{buildDir}/.vite/{manifestFilename}`

Default values: `publicPath=public`, `buildDir=build`, `manifestFilename=manifest.json`.

### Tag attribute resolution

Both `scriptTagAttributesResolvers` and `styleTagAttributesResolvers` are stacks of callables. Each resolver receives `($src, $url, $chunk, $manifest)` and returns an attribute array. Results are merged in order. Integrity hashes are pulled from the manifest entry using `integrityKey` (default: `'integrity'`; set to `false` to disable).

### HMR detection

HMR mode is active when the hotfile exists on disk. The hotfile contains the dev server base URL (e.g., `http://localhost:5173`). In HMR mode the `@vite/client` entry is prepended automatically.

## Testing

Tests use [Pest](https://pestphp.com/) with [vfsStream](https://github.com/bovigo/vfsStream) for virtual filesystem mocking and [Mockery](https://github.com/mockery/mockery) for mocks. Feature tests live in `tests/Feature/`, unit tests in `tests/Unit/`. Shared test helpers (e.g., `makeManifestFiles()`) are in `tests/Helpers.php`.

## Git Conventions

Use atomic commits — one logical change per commit. Follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>(<optional scope>): <description>
```

Common types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`.

## Code Standards

- PHP 8.5+ required; all files use `declare(strict_types=1)`
- PHPStan level 5 (`composer analyze`)
- Style enforced by PHP-CS-Fixer (config: `.php-cs-fixer.dist.php`) and StyleCI
- Rector configured for PHP 8.5 sets: `codeQuality`, `deadCode`, `instanceOf`, `privatization`, `typeDeclarations`
