<?php

declare(strict_types=1);

use Builtnoble\VitePHP\Vite;

/**
 * Create a manifest file with the given entries.
 */
function makeManifest(array $entries): void
{
    $path = __DIR__ . '/tmp/.vite/manifest.json';

    if (! is_dir($path)) {
        @mkdir(__DIR__ . '/tmp/.vite', 0o755, true);
    }

    if (file_exists($path)) {
        @unlink($path);
    }

    file_put_contents($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Create an invalid manifest file.
 */
function makeInvalidManifest(): void
{
    $path = __DIR__ . '/tmp/.vite/invalid_manifest.json';

    if (! is_dir($path)) {
        @mkdir(__DIR__ . '/tmp/.vite', 0o755, true);
    }

    if (file_exists($path)) {
        @unlink($path);
    }

    file_put_contents($path, '{ invalidJson: true,}');
}

/**
 * Initialize a Vite instance for testing.
 */
function initializeVite(?string $buildDir = null, ?string $publicDir = null): Vite
{
    test()->buildDir = $buildDir ?? 'tmp';
    test()->publicDir = $publicDir ?? 'tmp';

    test()->vite = new Vite();

    test()->vite->setBuildDir(test()->buildDir);
    test()->vite->setPublicDir(test()->publicDir);

    return test()->vite;
}
