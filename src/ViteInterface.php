<?php

declare(strict_types=1);

namespace Builtnoble\VitePHP;

interface ViteInterface
{
    public function __invoke(array $entries, ?string $buildDir = null): string;

    public function setAssetPathResolver(?callable $resolver): self;

    public function asset(string $path, ?string $buildDir = null, array $context = []): string;

    public function isRunningHot(): bool;

    public function getHotfile(): string;

    public function setHotfile(string $path): self;

    public function getNonce(): ?string;

    public function setNonce(?string $nonce = null): void;

    public function setBuildDir(string $path): self;

    public function setPublicDir(string $publicDir): self;

    public function setManifestFilename(string $manifestFilename): self;

    public function setIntegrityKey(false|string $key): self;

    public function setScriptTagAttributesResolvers(array|callable $attrs): self;

    public function setStyleTagAttributesResolvers(array|callable $attrs): self;
}
