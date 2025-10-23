<?php

namespace Builtnoble\VitePHP\Tests;

use Builtnoble\VitePHP\{ViteException, ViteInterface};
use Closure;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected MockInterface|ViteInterface $viteMock;

    protected ?Closure $creator = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viteMock = Mockery::mock(ViteInterface::class);

        $this->creator = fn () => $this->viteMock;

        $this->setDefaultViteBehavior();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function setDefaultViteBehavior(): void
    {
        // Default behavior for Vite
        $this->viteMock->shouldReceive('getHotfile')
            ->andReturn('public/hot')
            ->byDefault();

        $this->viteMock->shouldReceive('getNonce')
            ->andReturnNull()
            ->byDefault();

        $this->viteMock->shouldReceive('isRunningHot')
            ->andReturnFalse()
            ->byDefault();

        // Simulate asset method throwing exception to confirm the default
        // publicPath/buildDir/manifestFilename values
        $this->viteMock->shouldReceive('asset')
            ->withAnyArgs()
            ->andThrow(
                ViteException::class,
                'Vite manifest not found at path: public/build/.vite/manifest.json'
            )
            ->byDefault();
    }
}
