<?php

declare(strict_types=1);

use Builtnoble\VitePHP\Vite;

/**
 * Create a manifest file with the given entries.
 */
function makeManifest(array $entries, ?string $publicDir = '/tmp', ?string $buildDir = null): void
{
    $buildDir ??= 'build';
    $path = __DIR__ . $publicDir . "/{$buildDir}/.vite/manifest.json";

    if (! is_dir($path)) {
        @mkdir(__DIR__ . $publicDir . "/{$buildDir}/.vite", 0o755, true);
    }

    if (file_exists($path)) {
        @unlink($path);
    }

    file_put_contents($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Create an invalid manifest file.
 */
function makeInvalidManifest(?string $publicDir = '/tmp'): void
{
    $path = __DIR__ . $publicDir . '/build/.vite/invalid_manifest.json';

    if (! is_dir($path)) {
        @mkdir(__DIR__ . $publicDir . '/build/.vite', 0o755, true);
    }

    if (file_exists($path)) {
        @unlink($path);
    }

    file_put_contents($path, '{ invalidJson: true,}');
}

/**
 * Initialize a Vite instance for testing.
 */
function initializeVite(?string $publicDir = null): Vite
{
    test()->publicDir = $publicDir ?? __DIR__ . '/tmp';
    test()->buildDir = 'build';

    test()->vite = new Vite();
    test()->vite->setPublicPath(test()->publicDir);

    return test()->vite;
}
