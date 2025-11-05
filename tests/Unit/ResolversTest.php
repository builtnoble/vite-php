<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;

beforeAll(function () {
    makeManifestFiles([
        'app.js' => [
            'file' => 'app.123456.js',
            'src' => 'src/app.js',
        ],
        'app.css' => [
            'file' => 'app.654321.css',
            'src' => 'src/app.css',
        ],
    ]);
});

afterAll(function () {
    // Clean up the virtual file system
    vfsStream::setup();
});

describe('Unit/Resolvers', function () {
    beforeEach()->initializeVite();

    describe('Asset Path Resolver', function () {
        it('can set a resolver for asset paths', function () {
            $this->vite->setAssetPathResolver(fn (string $path) => str_replace($this->buildDir, 'cdn', $path));

            expect($this->vite->asset('app.js'))->toBe('cdn/app.123456.js')
                ->and($this->vite->asset('app.css'))->toBe('cdn/app.654321.css');
        });

        it('falls back to original asset path handling when resolver is set to null', function () {
            $this->vite->setAssetPathResolver(null);

            expect($this->vite->asset('app.js'))->toBe('build/app.123456.js')
                ->and($this->vite->asset('app.css'))->toBe('build/app.654321.css');
        });

        it('returns original asset path when HMR is enabled regardless of resolver being set', function () {
            $this->vite->setAssetPathResolver(fn (string $path) => str_replace($this->buildDir, 'cdn', $path));

            @file_put_contents("{$this->publicPath}/hot", 'https://localhost:3000');
            $viteServerUrl = @file_get_contents("{$this->publicPath}/hot");

            expect($this->vite->isRunningHot())->toBeTrue()
                ->and($this->vite->asset('app.js'))->toBe("{$viteServerUrl}/app.js")
                ->and($this->vite->asset('app.css'))->toBe("{$viteServerUrl}/app.css");

            @unlink("{$this->publicPath}/hot");
        });
    });

    describe('Attribute Resolvers', function () {
        it('can accept callable resolvers and apply the attributes to script and link tags', function () {
            $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['defer' => true]);
            $this->vite->setStyleTagAttributesResolvers(fn ($href, $url, $chunk, $manifest) => ['media' => 'print']);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" media="print" />',
                    '<script type="module" src="build/app.123456.js" defer></script>',
                ]) . PHP_EOL
            );
        });

        it('can accept associative array resolvers and apply the attributes to script and link tags', function () {
            $this->vite->setScriptTagAttributesResolvers(['defer' => true]);
            $this->vite->setStyleTagAttributesResolvers(['media' => 'print']);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" media="print" />',
                    '<script type="module" src="build/app.123456.js" defer></script>',
                ]) . PHP_EOL
            );
        });

        it('can accept multiple and mixed resolvers and merges the attributes correctly', function () {
            $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => ['defer' => true]);
            $this->vite->setScriptTagAttributesResolvers(['crossorigin' => 'anonymous']);

            $this->vite->setStyleTagAttributesResolvers(
                fn ($href, $url, $chunk, $manifest) => ['integrity' => 'sha384-xyz']
            );
            $this->vite->setStyleTagAttributesResolvers(['media' => 'print']);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" integrity="sha384-xyz" media="print" />',
                    '<script type="module" src="build/app.123456.js" defer crossorigin="anonymous"></script>',
                ]) . PHP_EOL
            );
        });

        it('does not add attributes if resolver returns an empty array', function () {
            $this->vite->setScriptTagAttributesResolvers(fn ($src, $url, $chunk, $manifest) => []);
            $this->vite->setStyleTagAttributesResolvers([]);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" />',
                    '<script type="module" src="build/app.123456.js"></script>',
                ]) . PHP_EOL
            );
        });

        it('handles non-callable array resolvers gracefully', function () {
            $this->vite->setScriptTagAttributesResolvers(['invalid' => null, 'async' => true]);
            $this->vite->setStyleTagAttributesResolvers(['invalid' => null, 'media' => 'all']);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" media="all" />',
                    '<script type="module" src="build/app.123456.js" async></script>',
                ]) . PHP_EOL
            );
        });
    });
});
