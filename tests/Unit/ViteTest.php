<?php

declare(strict_types=1);

use Builtnoble\VitePHP\{Vite, ViteException};
use org\bovigo\vfs\vfsStream;

covers(Vite::class);

beforeAll(function () {
    makeManifestFiles([
        'app.js' => [
            'file' => 'assets/app.abc123.js',
            'src' => 'src/app.js',
            'test-integrity-key' => 'sha256-xyz789',
        ],
        'app.css' => [
            'file' => 'assets/app.def456.css',
            'src' => 'src/app.css',
            'test-integrity-key' => 'sha256-xyz789',
        ],
        'shared.css' => [
            'file' => 'assets/shared.def456.css',
            'src' => 'src/shared.css',
        ],
        'shared.js' => [
            'file' => 'assets/shared.abc123.js',
            'src' => 'src/shared.js',
            'css' => ['assets/shared.def456.css'],
        ],
        'vendor.js' => [
            'file' => 'assets/vendor.ghi789.js',
            'src' => 'src/vendor.js',
            'imports' => ['shared.js'],
        ],
    ]);
});

afterAll(function () {
    // Clean up the virtual file system
    vfsStream::setup();
});

beforeEach()->initializeVite();

it('throws exception if the manifest file is missing', function () {
    $this->vite->setBuildDir('non/existent/path');

    expect(fn () => ($this->vite)(['app.js', 'app.css']))
        ->toThrow(
            ViteException::class,
            "Vite manifest not found at path: {$this->publicPath}/non/existent/path/.vite/manifest.json"
        );
});

it('throws exception if manifest is not valid JSON', function () {
    $this->vite->setManifestFilename('invalid_manifest.json');

    expect(fn () => ($this->vite)(['app.js', 'app.css']))
        ->toThrow(
            ViteException::class,
            "Invalid JSON in Vite manifest file at: {$this->publicPath}/build/.vite/invalid_manifest.json"
        );
});

it('throws exception if entry is missing in manifest', function () {
    expect(fn () => ($this->vite)(['missing_entry.js']))->toThrow(
        ViteException::class,
        'Unable to find entry in Vite manifest: missing_entry.js'
    );
});

it('respects explicit buildDir when provided to __invoke', function () {
    $this->vite->setBuildDir('other');

    expect(($this->vite)(['app.js'], 'build'))->toBe(
        "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>" . PHP_EOL
    );
});

it('respects explicit buildDir when provided to asset()', function () {
    $this->vite->setBuildDir('other');

    expect($this->vite->asset('app.js', 'build'))->toBe("{$this->buildDir}/assets/app.abc123.js");
});

it('removes duplicate stylesheet tags across entries', function () {
    // shared.js and vendor.js both reference the same shared.css (vendor imports shared.js)
    expect(($this->vite)(['shared.js', 'vendor.js']))->toBe(
        implode(PHP_EOL, [
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/shared.def456.css\" />",
            "<script type=\"module\" src=\"{$this->buildDir}/assets/shared.abc123.js\"></script>",
            "<script type=\"module\" src=\"{$this->buildDir}/assets/vendor.ghi789.js\"></script>",
        ]) . PHP_EOL
    );
});

it('selects the correct manifest key when multiple entries reference the same CSS file', function () {
    // create a custom build dir with a manifest containing duplicate CSS file values
    $buildDir = 'dupbuild';
    $manifestDir = "{$this->publicPath}/{$buildDir}/.vite";
    if (! is_dir($manifestDir)) {
        @mkdir($manifestDir, 0o755, true);
    }

    $manifest = [
        // two distinct keys that both reference the same CSS file
        'dup1' => [
            'file' => 'assets/shared.def456.css',
            'src' => 'src/dup1.css',
        ],
        'dup2' => [
            'file' => 'assets/shared.def456.css',
            'src' => 'src/dup2.css',
        ],
        // an entry that uses that CSS file
        'shared.js' => [
            'file' => 'assets/shared.abc123.js',
            'src' => 'src/shared.js',
            'css' => ['assets/shared.def456.css'],
        ],
    ];

    @file_put_contents("{$manifestDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

    $this->vite->setBuildDir($buildDir);

    // add a style resolver that exposes the chosen manifest key via data-src attribute
    $this->vite->setStyleTagAttributesResolvers(
        fn (string $src, string $url, ?array $chunk = null, ?array $m = null) => ['data-src' => $src]
    );

    expect(($this->vite)('shared.js'))->toBe(
        implode(PHP_EOL, [
            '<link rel="stylesheet" href="dupbuild/assets/shared.def456.css" data-src="dup1" />',
            '<script type="module" src="dupbuild/assets/shared.abc123.js"></script>',
        ]) . PHP_EOL
    );

    @unlink("{$manifestDir}/manifest.json");
});

it('resolves correct manifest key for imported styles when using a style resolver', function () {
    // Add a resolver that exposes the manifest key used to create the stylesheet tag.
    $this->vite->setStyleTagAttributesResolvers(
        fn (string $src, string $url, ?array $chunk = null, ?array $m = null) => ['data-src' => $src]
    );

    // vendor.js imports shared.js which references assets/shared.def456.css,
    // so the resolver should receive 'shared.js' as the manifest key.
    expect(($this->vite)('vendor.js'))->toBe(
        implode(PHP_EOL, [
            '<link rel="stylesheet" href="build/assets/shared.def456.css" data-src="shared.css" />',
            '<script type="module" src="build/assets/vendor.ghi789.js"></script>',
        ]) . PHP_EOL
    );
});

it('concatenates multiple stylesheet tags without injecting an unexpected separator', function () {
    expect(($this->vite)(['app.css', 'shared.css']))->toBe(
        implode(PHP_EOL, [
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/shared.def456.css\" />",
        ]) . PHP_EOL
    );
});

it('generates correct script tag for single JS entry', function () {
    expect(($this->vite)('app.js'))
        ->toBe(
            "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>" . PHP_EOL
        );
});

it('generates correct link tag for single CSS entry', function () {
    expect(($this->vite)('app.css'))->toBe(
        "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />" . PHP_EOL
    );
});

it('generates tags for valid entries with associated CSS and JS', function () {
    expect(($this->vite)('shared.js'))->toBe(
        implode(
            PHP_EOL,
            [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/shared.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/shared.abc123.js\"></script>",
            ]
        ) . PHP_EOL
    );
});

it('generates correct tags for multiple entries', function () {
    expect(($this->vite)(['app.js', 'app.css']))->toBe(
        implode(PHP_EOL, [
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
            "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>",
        ]) . PHP_EOL
    );
});

it('generates correct tags for entry with imports', function () {
    expect(($this->vite)('vendor.js'))->toBe(
        implode(PHP_EOL, [
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/shared.def456.css\" />",
            "<script type=\"module\" src=\"{$this->buildDir}/assets/vendor.ghi789.js\"></script>",
        ]) . PHP_EOL
    );
});
