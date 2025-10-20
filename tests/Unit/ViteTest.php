<?php

declare(strict_types=1);

use Builtnoble\VitePHP\{Vite, ViteException};

covers(Vite::class);

beforeAll(function () {
    makeManifest([
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

    makeInvalidManifest();
});

beforeEach()->initializeVite(
    buildDir: dirname(__DIR__) . '/tmp',
    publicDir: dirname(__DIR__) . '/tmp/public'
);

describe('Exception handling', function () {
    it('throws exception if the manifest file is missing', function () {
        $this->vite->setBuildDir('non/existent/path');
        ($this->vite)(['app.js', 'app.css']);
    })->throws(
        ViteException::class,
        'Vite manifest not found at path: non/existent/path/.vite/manifest.json'
    );

    it('throws exception if entry is missing in manifest', function () {
        ($this->vite)(['missing_entry.js']);
    })->throws(
        ViteException::class,
        'Unable to find entry in Vite manifest: missing_entry.js'
    );

    it('throws exception if manifest is not valid JSON', function () {
        $this->vite->setManifestFilename('invalid_manifest.json');

        expect(fn () => ($this->vite)(['app.js', 'app.css']))->toThrow(
            ViteException::class,
            "Invalid JSON in Vite manifest file at: {$this->buildDir}/.vite/invalid_manifest.json"
        );
    });
});

describe('Asset path resolving', function () {
    it('applies assetPathResolver for generated tags via __invoke', function () {
        $this->vite->setAssetPathResolver(fn ($path, $context) => str_replace($this->buildDir, '/cdn', $path));

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                '<link rel="stylesheet" href="/cdn/assets/app.def456.css" />',
                '<script type="module" src="/cdn/assets/app.abc123.js"></script>',
            ]) . PHP_EOL
        );
    });

    it('applies assetPathResolver with context when using asset()', function () {
        // Ensure HMR is disabled for this test.
        $this->vite->setHotfile('/tmp/nonexistent-hotfile');

        // Append a version query param derived from the supplied context.
        $this->vite->setAssetPathResolver(fn ($path, $context) => $path . '?v=' . ($context['version'] ?? ''));

        $result = $this->vite->asset('app.js', null, ['version' => '1.2']);

        expect($result)->toBe("{$this->buildDir}/assets/app.abc123.js?v=1.2");
    });

    it('falls back to original path when resolver is null', function () {
        $this->vite->setAssetPathResolver(null);

        expect(($this->vite)(['app.js']))->toBe(
            "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>" . PHP_EOL
        );
    });

    it('returns original path if HMR is enabled', function () {
        if (! is_dir($this->publicDir)) {
            @mkdir($this->publicDir, 0o755, true);
        }

        if (! file_exists("{$this->publicDir}/hot")) {
            @file_put_contents("{$this->publicDir}/hot", '');
        }

        expect($this->vite->asset('app.js'))->toBe('/app.js');
    });
});

describe('Tag generation', function () {
    it('generates correct script tag for single JS entry', function () {
        expect(($this->vite)(['app.js']))->toBe(
            "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>" . PHP_EOL
        );
    });

    it('generates correct link tag for single CSS entry', function () {
        expect(($this->vite)(['app.css']))->toBe(
            "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />" . PHP_EOL
        );
    });

    it('generates tags for valid entries with associated CSS and JS', function () {
        expect(($this->vite)(['shared.js']))->toBe(
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
        expect(($this->vite)(['vendor.js']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/shared.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/vendor.ghi789.js\"></script>",
            ]) . PHP_EOL
        );
    });
});

describe('Attribute resolving', function () {
    it('adds a callable resolver and applies attributes to tags', function () {
        $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['defer' => true]);
        $this->vite->setStyleTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['media' => 'screen']);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" media=\"screen\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" defer></script>",
            ]) . PHP_EOL
        );
    });

    it('adds an array resolver and applies attributes to tags', function () {
        $this->vite->setScriptTagAttributesResolvers(['async' => true, 'data-custom' => 'value']);
        $this->vite->setStyleTagAttributesResolvers(['media' => 'print', 'data-custom' => 'value']);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" media=\"print\" data-custom=\"value\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" async data-custom=\"value\"></script>",
            ]) . PHP_EOL
        );
    });

    it('handles multiple resolvers and merges attributes correctly', function () {
        $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['async' => true]);
        $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['data-custom' => 'value']);

        $this->vite->setStyleTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['media' => 'screen']);
        $this->vite->setStyleTagAttributesResolvers(['data-custom' => 'value']);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" media=\"screen\" data-custom=\"value\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" async data-custom=\"value\"></script>",
            ]) . PHP_EOL
        );
    });

    it('does not add attributes if resolver returns an empty array', function () {
        $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => []);
        $this->vite->setStyleTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => []);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>",
            ]) . PHP_EOL
        );
    });

    it('handles non-callable array resolvers gracefully', function () {
        $this->vite->setScriptTagAttributesResolvers(['invalid' => null, 'async' => true]);
        $this->vite->setStyleTagAttributesResolvers(['invalid' => null, 'media' => 'screen']);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" media=\"screen\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" async></script>",
            ]) . PHP_EOL
        );
    });
});

describe('Tag attributes', function () {
    it('adds nonce attribute if nonce is set with a string', function () {
        $nonce = 'test-nonce';

        $this->vite->setNonce($nonce);

        expect($this->vite->getNonce())->toBe($nonce)
            ->and(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" nonce=\"{$nonce}\" />",
                    "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" nonce=\"{$nonce}\"></script>",
                ]) . PHP_EOL
            );
    });

    it('adds nonce attribute if nonce is set w/o a string', function () {
        $this->vite->setNonce();
        $nonce = $this->vite->getNonce();

        expect($this->vite->getNonce())->toBeString()
            ->and(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" nonce=\"{$nonce}\" />",
                    "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" nonce=\"{$nonce}\"></script>",
                ]) . PHP_EOL
            );
    });

    it('adds integrity attribute if integrityKey is set with a string', function () {
        $this->vite->setIntegrityKey('test-integrity-key');

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" integrity=\"sha256-xyz789\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\" integrity=\"sha256-xyz789\"></script>",
            ]) . PHP_EOL
        );
    });

    it('does not add integrity attribute if integrityKey is set but missing in manifest', function () {
        $this->vite->setIntegrityKey('missing-integrity-key');

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>",
            ]) . PHP_EOL
        );
    });

    it('does not add integrity attribute if integrityKey is not set', function () {
        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>",
            ]) . PHP_EOL
        );
    });

    it('does not set add integrity attribute if integrityKey is set to false', function () {
        $this->vite->setIntegrityKey(false);

        expect(($this->vite)(['app.js', 'app.css']))->toBe(
            implode(PHP_EOL, [
                "<link rel=\"stylesheet\" href=\"{$this->buildDir}/assets/app.def456.css\" />",
                "<script type=\"module\" src=\"{$this->buildDir}/assets/app.abc123.js\"></script>",
            ]) . PHP_EOL
        );
    });
});

describe('HMR (Hot Module Replacement)', function () {
    beforeEach(function () {
        if (! is_dir($this->publicDir)) {
            @mkdir($this->publicDir, 0o755, true);
        }

        if (! file_exists("{$this->publicDir}/hot")) {
            @file_put_contents("{$this->publicDir}/hot", '');
        }
    });

    it('sets the hotfile path and returns the instance', function () {
        $this->vite->setHotfile('/custom/path/to/hotfile');

        expect($this->vite->getHotfile())->toBe('/custom/path/to/hotfile');
    });

    it('overrides the default hotfile path', function () {
        $this->vite->setHotfile('/new/hotfile/path');

        expect($this->vite->getHotfile())->toBe('/new/hotfile/path');
    });

    it('returns default hotfile path when no custom path is set', function () {
        expect($this->vite->getHotfile())->toBe("{$this->publicDir}/hot");
    });

    it('checks if HMR server is running when hotfile exists', function () {
        expect($this->vite->getHotfile())->toBe("{$this->publicDir}/hot")
            ->and($this->vite->isRunningHot())->toBeTrue();
    });

    it('checks if HMR server is not running when hotfile does not exist', function () {
        $this->vite->setHotfile('/tmp/nonexistent-hotfile');

        expect($this->vite->isRunningHot())->toBeFalse();
    });
});
