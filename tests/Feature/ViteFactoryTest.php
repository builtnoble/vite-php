<?php

declare(strict_types=1);

use Builtnoble\VitePHP\{Vite, ViteException, ViteFactory};

covers(ViteFactory::class);

beforeEach()->initializeVite(
    buildDir: dirname(__DIR__) . '/tmp',
    publicDir: dirname(__DIR__) . '/tmp/public',
    mock: true
);

describe('Vite Factory', function () {
    it('throws an exception if the creator does not return a Vite instance', function (): void {
        $invalidCreator = fn () => new stdClass();

        expect(fn () => ViteFactory::make([], $invalidCreator))
            ->toThrow(
                ViteException::class,
                'The creator callable must return an instance of ' . Vite::class
            );
    });

    it('applies basic options and resolvers', function () {
        $assetResolver = fn (string $path): string => '/assets/' . $path;
        $scriptAttrResolverA = fn (): array => ['defer' => true];
        $scriptAttrResolverB = ['data-test' => 'x'];
        $styleAttrResolver = fn (): array => ['media' => 'screen'];

        $options = [
            'assetPathResolver' => $assetResolver,
            'hotfile' => '/tmp/hot',
            'buildDir' => 'dist',
            'publicDir' => 'public',
            'manifestFilename' => 'manifest.json',
            'integrityKey' => 'secret-key',
            'nonce' => 'fixed-nonce',
            'scriptTagAttributesResolvers' => [$scriptAttrResolverA, $scriptAttrResolverB],
            'styleTagAttributesResolvers' => $styleAttrResolver,
        ];

        $this->vite->shouldReceive('setAssetPathResolver')
            ->with($options['assetPathResolver'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setScriptTagAttributesResolvers')
            ->with($scriptAttrResolverA)
            ->once()
            ->andReturnSelf();

        $this->vite->shouldReceive('setScriptTagAttributesResolvers')
            ->with($scriptAttrResolverB)
            ->once()
            ->andReturnSelf();

        $this->vite->shouldReceive('setStyleTagAttributesResolvers')
            ->with($styleAttrResolver)
            ->once()
            ->andReturnSelf();

        $this->vite->shouldReceive('setNonce')
            ->with($options['nonce'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setHotfile')
            ->with($options['hotfile'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setBuildDir')
            ->with($options['buildDir'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setPublicDir')
            ->with($options['publicDir'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setManifestFilename')
            ->with($options['manifestFilename'])
            ->andReturnSelf();

        $this->vite->shouldReceive('setIntegrityKey')
            ->with($options['integrityKey'])
            ->andReturnSelf();

        $viteFactory = ViteFactory::make($options, fn () => $this->vite);

        expect($viteFactory)->toBeInstanceOf(Vite::class)
            ->and($viteFactory)->toBe($this->vite);
    });
});
