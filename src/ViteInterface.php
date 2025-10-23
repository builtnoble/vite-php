<?php

declare(strict_types=1);

namespace Builtnoble\VitePHP;

use Random\RandomException;

interface ViteInterface
{
    /**
     * Traverse the given entries and return as HTML script and link tags.
     *
     * @throws ViteException if manifest or entry cannot be found or manifest has
     *                       invalid json
     */
    public function __invoke(array $entries, ?string $buildDir = null): string;

    /**
     * Set a custom resolver for returning asset paths. For example, Twig has an
     * asset() function that can be used to return the correct (public) path for
     * a given asset.
     */
    public function setAssetPathResolver(?callable $resolver): self;

    /**
     * Return the public path for a given asset.
     *
     * @throws ViteException if manifest file cannot be found or has invalid json
     */
    public function asset(string $path, ?string $buildDir = null, array $context = []): string;

    /**
     * Determine if Vite is running in hot module replacement mode.
     */
    public function isRunningHot(): bool;

    /**
     * Get the path to the hot file.
     */
    public function getHotfile(): string;

    /**
     * Set the path to the hot file.
     */
    public function setHotfile(string $path): self;

    /**
     * Get the current nonce value.
     */
    public function getNonce(): ?string;

    /**
     * Set a nonce value to be added to script and link tags. If null, a random
     * 40-character string will be generated.
     *
     * @throws RandomException if random string generation fails
     */
    public function setNonce(?string $value = null): void;

    /**
     * Set the name of the directory where Vite will store its manifest file
     * and any compiled assets.
     */
    public function setBuildDir(string $name): self;

    /**
     * Set the path to where public files, like static assets, are located.
     */
    public function setPublicPath(string $path): self;

    /**
     * Set the filename of the Vite manifest file.
     */
    public function setManifestFilename(string $value): self;

    /**
     * Set the key in the manifest file that contains the integrity hash. Set as
     * false to disable integrity checks.
     */
    public function setIntegrityKey(false|string $value): self;

    /**
     * Set any additional attributes that should be added to script tags when
     * they are generated. This can be an associative array of attributes or a
     * callable that returns such an array.
     */
    public function setScriptTagAttributesResolvers(array|callable $attrs): self;

    /**
     * Set any additional attributes that should be added to link tags when they
     * are generated. This can be an associative array of attributes or a callable
     * that returns such an array.
     */
    public function setStyleTagAttributesResolvers(array|callable $attrs): self;
}
