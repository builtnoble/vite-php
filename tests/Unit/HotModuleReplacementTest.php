<?php

declare(strict_types=1);

use org\bovigo\vfs\vfsStream;

beforeAll(function (): void {
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

afterAll(function (): void {
    // Clean up the virtual file system
    vfsStream::setup();
});

describe('HMR (Hot Module Replacement)', function (): void {
    beforeEach(function (): void {
        if (! is_dir($this->publicPath)) {
            @mkdir($this->publicPath, 0o755, true);
        }

        if (! file_exists("{$this->publicPath}/hot")) {
            @file_put_contents("{$this->publicPath}/hot", 'http://localhost:5173');
        }
    })->initializeVite();

    it('sets the hotfile path and returns the instance', function (): void {
        $this->vite->setHotfile('/custom/path/to/hotfile');

        expect($this->vite->getHotfile())->toBe('/custom/path/to/hotfile');
    });

    it('overrides the default hotfile path', function (): void {
        $this->vite->setHotfile('/new/hotfile/path');

        expect($this->vite->getHotfile())->toBe('/new/hotfile/path');
    });

    it('returns default hotfile path when no custom path is set', function (): void {
        expect($this->vite->getHotfile())->toBe("{$this->publicPath}/hot");
    });

    it('checks if HMR server is running when hotfile exists', function (): void {
        expect($this->vite->getHotfile())->toBe("{$this->publicPath}/hot")
            ->and($this->vite->isRunningHot())->toBeTrue();
    });

    it('checks if HMR server is not running when hotfile does not exist', function (): void {
        $this->vite->setHotfile('/tmp/nonexistent-hotfile');

        expect($this->vite->isRunningHot())->toBeFalse();
    });

    it('returns hot asset using hotfile base url when hotfile contains a URL', function (): void {
        expect($this->vite->asset('app.js'))->toBe('http://localhost:5173/app.js');
    });

    it('trims whitespace/newline from hotfile when returning hot asset base url', function (): void {
        @file_put_contents("{$this->publicPath}/hot", "http://localhost:5173\n");

        expect($this->vite->asset('app.js'))->toBe('http://localhost:5173/app.js');
    });
});
