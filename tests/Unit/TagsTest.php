<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;

beforeAll(function (): void {
    makeManifestFiles([
        'app.js' => [
            'file' => 'app.123456.js',
            'src' => 'src/app.js',
            'test-integrity-key' => 'sha256-xyz789',
        ],
        'app.css' => [
            'file' => 'app.654321.css',
            'src' => 'src/app.css',
            'test-integrity-key' => 'sha256-xyz789',
        ],
    ]);
});

afterAll(function (): void {
    // Clean up the virtual file system
    vfsStream::setup();
});

describe('Unit/Tags', function (): void {
    beforeEach()->initializeVite();

    describe('Attributes', function (): void {
        it('adds nonce attribute if nonce is set with a string', function (): void {
            $nonce = 'test-nonce';

            $this->vite->setNonce($nonce);

            expect($this->vite->getNonce())->toBe($nonce)
                ->and(($this->vite)(['app.js', 'app.css']))->toBe(
                    implode(PHP_EOL, [
                        "<link rel=\"stylesheet\" href=\"build/app.654321.css\" nonce=\"{$nonce}\" />",
                        "<script type=\"module\" src=\"build/app.123456.js\" nonce=\"{$nonce}\"></script>",
                    ]) . PHP_EOL
                );
        });

        it('generates 40 character random string when calling setNonce w/o argument', function (): void {
            $this->vite->setNonce();
            $nonce = $this->vite->getNonce();

            expect($this->vite->getNonce())->toBeString()
                ->and(strlen($nonce))->toBe(40)
                ->and(($this->vite)(['app.js', 'app.css']))->toBe(
                    implode(PHP_EOL, [
                        "<link rel=\"stylesheet\" href=\"build/app.654321.css\" nonce=\"{$nonce}\" />",
                        "<script type=\"module\" src=\"build/app.123456.js\" nonce=\"{$nonce}\"></script>",
                    ]) . PHP_EOL
                );
        });

        it('adds integrity attribute if integrityKey is set with a string', function (): void {
            $this->vite->setIntegrityKey('test-integrity-key');

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" integrity="sha256-xyz789" />',
                    '<script type="module" src="build/app.123456.js" integrity="sha256-xyz789"></script>',
                ]) . PHP_EOL
            );
        });

        it('does not add integrity attribute if integrityKey is set but missing in manifest', function (): void {
            $this->vite->setIntegrityKey('missing-integrity-key');

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" />',
                    '<script type="module" src="build/app.123456.js"></script>',
                ]) . PHP_EOL
            );
        });

        it('does not add integrity attribute if integrityKey is not set', function (): void {
            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" />',
                    '<script type="module" src="build/app.123456.js"></script>',
                ]) . PHP_EOL
            );
        });

        it('does not set add integrity attribute if integrityKey is set to false', function (): void {
            $this->vite->setIntegrityKey(false);

            expect(($this->vite)(['app.js', 'app.css']))->toBe(
                implode(PHP_EOL, [
                    '<link rel="stylesheet" href="build/app.654321.css" />',
                    '<script type="module" src="build/app.123456.js"></script>',
                ]) . PHP_EOL
            );
        });
    });
});
