<?php

declare(strict_types=1);

use Builtnoble\VitePHP\{ViteException, ViteFactory, ViteInterface};

covers(ViteFactory::class);

describe('Vite Factory', function () {
    it('throws exception if creator is not a valid Vite instance', function () {
        $invalidCreator = fn () => new stdClass();

        expect(fn () => ViteFactory::make(creator: $invalidCreator))->toThrow(
            ViteException::class,
            'The creator callable must return an instance of Builtnoble\VitePHP\Vite'
        );
    });

    it('creates a Vite instance with no options provided', function () {
        $viteFactory = ViteFactory::make(creator: $this->creator);

        expect($viteFactory)->toBeInstanceOf(ViteInterface::class)
            ->and($viteFactory->getHotfile())->toBe('public/hot')
            ->and($viteFactory->getNonce())->toBeNull()
            ->and($viteFactory->isRunningHot())->toBeFalse()
            ->and(fn () => $viteFactory->asset('app.js'))->toThrow(
                ViteException::class,
                'Vite manifest not found at path: public/build/.vite/manifest.json'
            );
    });

    it('casts string options to string when non-string is provided', function () {
        $options = [
            'hotfile' => 123,
            'buildDir' => 456,
            'publicPath' => 789,
            'manifestFilename' => 101112,
        ];

        $message = "Vite manifest not found at path: {$options['publicPath']}/{$options['buildDir']}/.vite/{$options['manifestFilename']}";

        $this->viteMock->shouldReceive('setHotfile')
            ->with('123')
            ->once()
            ->andReturnSelf();

        $this->viteMock->shouldReceive('setBuildDir')
            ->with('456')
            ->once()
            ->andReturnSelf();

        $this->viteMock->shouldReceive('setPublicPath')
            ->with('789')
            ->once()
            ->andReturnSelf();

        $this->viteMock->shouldReceive('setManifestFilename')
            ->with('101112')
            ->once()
            ->andReturnSelf();

        $this->viteMock->shouldReceive('getHotfile')
            ->once()
            ->andReturn('123');

        $this->viteMock->shouldReceive('asset')
            ->once()
            ->withAnyArgs()
            ->andThrow(ViteException::class, $message);

        $viteFactory = ViteFactory::make($options, $this->creator);

        expect($viteFactory->getHotfile())->toBe('123')
            ->and(fn () => $viteFactory->asset('app.js'))->toThrow(
                ViteException::class,
                $message
            );
    });

    it('converts callable resolvers to an array', function () {
        $scriptResolver = fn (): array => ['defer' => true];
        $styleResolver = fn (): array => ['media' => 'all'];

        $html = implode(PHP_EOL, [
            '<script src="assets/app.abc123.js" defer></script>',
            '<link rel="stylesheet" href="assets/app.def456.css" media="all">',
        ]);

        $this->viteMock->shouldReceive('setScriptTagAttributesResolvers')
            ->once()
            ->with($scriptResolver)
            ->andReturnSelf();

        $this->viteMock->shouldReceive('setStyleTagAttributesResolvers')
            ->once()
            ->with($styleResolver)
            ->andReturnSelf();

        $this->viteMock->shouldReceive('__invoke')
            ->once()
            ->with(['app.js', 'app.css'])
            ->andReturn($html);

        $viteFactory = ViteFactory::make([
            'scriptTagAttributesResolvers' => $scriptResolver,
            'styleTagAttributesResolvers' => $styleResolver,
        ], $this->creator);

        expect(($viteFactory)(['app.js', 'app.css']))->toBe($html);
    });

    it('does not call setter for empty array resolvers', function () {
        $this->viteMock->shouldReceive('setScriptTagAttributesResolvers')->never();

        $vite = ViteFactory::make(['scriptTagAttributesResolvers' => []], $this->creator);

        expect($vite)->toBeInstanceOf(ViteInterface::class);
    });

    it('accepts a callable asset path resolver', function () {
        $resolver = fn (string $path): string => '/custom/' . ltrim($path, '/');

        $this->viteMock->shouldReceive('setAssetPathResolver')
            ->with($resolver)
            ->once()
            ->andReturnSelf();

        $this->viteMock->shouldReceive('asset')
            ->with('app.js')
            ->once()
            ->andReturn('/custom/build/assets/app.abc123.js');

        $viteFactory = ViteFactory::make([
            'assetPathResolver' => $resolver,
        ], $this->creator);

        expect($viteFactory->asset('app.js'))->toBe('/custom/build/assets/app.abc123.js');
    });

    it('accepts a string for integrityKey option', function () {
        $html = '<script src="assets/app.abc123.js" integrity="sha256-xyz789"></script>';

        $this->viteMock->shouldReceive('setIntegrityKey')
            ->once()
            ->with('my-integrity-key')
            ->andReturnSelf();

        $this->viteMock->shouldReceive('__invoke')
            ->once()
            ->with(['app.js'])
            ->andReturn($html);

        $viteFactory = ViteFactory::make([
            'integrityKey' => 'my-integrity-key',
        ], $this->creator);

        expect(($viteFactory)(['app.js']))->toBe($html);
    });

    it('accepts false for integrityKey option', function () {
        $html = '<script src="assets/app.abc123.js"></script>';

        $this->viteMock->shouldReceive('setIntegrityKey')
            ->once()
            ->with(false)
            ->andReturnSelf();

        $this->viteMock->shouldReceive('__invoke')
            ->once()
            ->with(['app.js'])
            ->andReturn($html);

        $viteFactory = ViteFactory::make([
            'integrityKey' => false,
        ], $this->creator);

        expect(($viteFactory)(['app.js']))->toBe($html);
    });

    it('accepts null to generate a random nonce', function () {
        $this->viteMock->shouldReceive('setNonce')
            ->once()
            ->with(null)
            ->andReturnSelf();

        $this->viteMock->shouldReceive('getNonce')
            ->once()
            ->andReturn('randomly-generated-nonce');

        $viteFactory = ViteFactory::make([
            'nonce' => null,
        ], $this->creator);

        expect($viteFactory->getNonce())->toBe('randomly-generated-nonce');
    });
});
