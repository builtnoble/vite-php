<div style="text-align: center">
    <h1>Vite Manifest Reader for PHP</h1>
    <a href="https://github.styleci.io/repos/1078430247?branch=main"><img src="https://github.styleci.io/repos/1078430247/shield?branch=main" alt="StyleCI"></a>
</div>

---

Laravel provides an excellent built-in Vite integration through its Vite class but, for developers working outside of
Laravel’s ecosystem, there’s a gap.

This package aims to fill that gap, making it easy to integrate Vite into standalone PHP applications, micro-frameworks,
or custom setups while keeping a clean and familiar workflow.

It reads Vite’s `manifest.json` file, resolves asset URLs, and outputs the correct `<script>` and `<link>` tags for both
development and production environments.

While it can be used on its own, it’s primarily intended as a core utility for framework-agnostic Vite integrations —
for example, as part of a [Plates](https://platesphp.com/) or [Twig](https://twig.symfony.com/) extension.

---

## Usage

Install via composer:

```bash
composer require builtnoble/vite-php
```

Then, use it in your PHP code:

```php
use Builtnoble\VitePHP\ViteFactory;
use Random\RandomException;

// Basic usage (standalone):
// Build a configured Vite instance and invoke it with an array of asset paths.
// Passing 'nonce' => null requests a generated nonce (may throw RandomException).
try {
    $vite = ViteFactory::make([
        'hotfile' => '/tmp/vite-dev-server',
        'buildDir' => 'dist',
        'publicDir' => 'public',
        'manifestFilename' => 'manifest.json',
        'integrityKey' => 'my_integrity_key',
        'nonce' => null, // pass null to generate a random nonce
    ]);

    // invoke Vite with an array of asset paths; the invokable returns the rendered tags/HTML
    echo ($vite)([
        'resources/css/app.css',
        'resources/js/app.js',
    ]);
} catch (RandomException $e) {
    // handle nonce generation failure
    throw $e;
}

// Advanced: provide a custom creator and attribute resolvers
$customCreator = function (): Builtnoble\VitePHP\Vite {
    $v = new Builtnoble\VitePHP\Vite();
    $v->setBuildDir('custom-dist');
    return $v;
};

$vite = ViteFactory::make([
    'scriptTagAttributesResolvers' => [
        // resolver receives (string $path, ?array $manifestEntry) and returns attributes
        function (string $path, array $entry = null): array {
            return ['defer' => true];
        },
    ],
    'styleTagAttributesResolvers' => function (string $path, array $entry = null): array {
        return ['media' => 'all'];
    },
], $customCreator);

// invoke with specific assets
echo ($vite)([
    'resources/js/app.js',
]);
```
