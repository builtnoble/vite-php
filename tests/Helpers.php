<?php

declare(strict_types=1);

use Builtnoble\VitePHP\Vite;
use org\bovigo\vfs\vfsStream;

/**
 * Create a manifest file with the given entries and a manifest file with invalid
 * JSON.
 */
function makeManifestFiles(array $entries, ?string $publicPath = '/public', ?string $buildDir = 'build'): void
{
    vfsStream::setup(trim($publicPath, '/'), null, [
        trim($buildDir, '/') => [
            '.vite' => [
                'manifest.json' => json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'invalid_manifest.json' => '{ invalidJson: true,}',
            ],
        ],
    ]);
}

/**
 * Initialize a Vite instance for testing.
 */
function initializeVite(): Vite
{
    test()->publicPath = vfsStream::url('public');
    test()->buildDir = 'build';

    test()->vite = new Vite();
    test()->vite->setPublicPath(test()->publicPath);

    return test()->vite;
}
