# AGENTS.md

This file helps AI coding agents understand how to integrate `builtnoble/vite-php` into a PHP project.

## What this library does

`builtnoble/vite-php` reads Vite's `manifest.json` and outputs the correct `<script type="module">` and `<link rel="stylesheet">` HTML tags for a PHP application. It handles both production (manifest-based) and development (HMR/hot-reload) modes automatically.

It is **not** a Laravel package — it is framework-agnostic and works with any PHP 8.5+ project, including raw PHP, Slim, Mezzio, or custom templating engine extensions.

## How to integrate it

### 1. Install

```bash
composer require builtnoble/vite-php
```

### 2. Create a Vite instance

Use `ViteFactory::make()` with options that match your project layout:

```php
use Builtnoble\VitePHP\ViteFactory;

$vite = ViteFactory::make([
    'publicPath' => __DIR__ . '/public', // absolute or relative path to public dir
    'buildDir'   => 'build',             // must match build.outDir in vite.config.js
]);
```

### 3. Output tags in a template

The `$vite` instance is invokable. Pass an array of entry point paths (matching the keys in your Vite config's `input`):

```php
// In a PHP template or layout file:
echo ($vite)(['resources/js/app.js', 'resources/css/app.css']);
```

This outputs stylesheets first, then scripts. In HMR mode it points to the dev server; in production it reads the manifest.

### 4. Vite config requirements

```js
// vite.config.js
export default {
  build: {
    manifest: true,           // or manifest: 'manifest.json'
    outDir: 'public/build',   // must match publicPath + buildDir above
    rollupOptions: {
      input: ['resources/js/app.js', 'resources/css/app.css'],
    },
  },
}
```

## Key concepts for agents

- **Manifest path**: resolved as `{publicPath}/{buildDir}/.vite/{manifestFilename}` — default: `public/build/.vite/manifest.json`
- **HMR mode**: active when the hotfile exists (default: `{publicPath}/hot`). No manifest is read in this mode.
- **Type-hint on `ViteInterface`**: the concrete `Vite` class is `final`; use `ViteInterface` for dependency injection.
- **Errors**: `ViteException` covers missing manifest, bad JSON, and missing entry points.

## Common integration patterns

### Dependency injection / service container

```php
$container->singleton(ViteInterface::class, fn () => ViteFactory::make([
    'publicPath' => BASE_PATH . '/public',
    'buildDir'   => 'build',
]));
```

### Twig extension

```php
class ViteExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(private ViteInterface $vite) {}

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('vite', fn (array $entries) => new \Twig\Markup(($this->vite)($entries), 'UTF-8')),
        ];
    }
}
```

### CSP nonce

```php
$vite = ViteFactory::make(['nonce' => null]); // generates a random nonce
header("Content-Security-Policy: script-src 'nonce-" . $vite->getNonce() . "'");
echo ($vite)(['resources/js/app.js']);
```

### CDN asset URLs

```php
$vite = ViteFactory::make([
    'assetPathResolver' => fn (string $path): string => 'https://cdn.example.com/' . $path,
]);
```

### Single asset URL (for use in `<img src>`, etc.)

```php
$logoUrl = $vite->asset('resources/images/logo.png');
```
