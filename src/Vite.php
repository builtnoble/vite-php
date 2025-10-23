<?php

declare(strict_types=1);

namespace Builtnoble\VitePHP;

use Random\RandomException;

final class Vite implements ViteInterface
{
    /**
     * Nonce value for Content Security Policy (CSP).
     */
    private ?string $nonce = null;

    /**
     * The name of the hot module replacement (HMR) file.
     */
    private string $hotfile;

    /**
     * The name of directory where Vite will store its manifest file and any
     * compiled assets.
     */
    private string $buildDir = 'build';

    /**
     * The path to where public files, like static assets, are located.
     */
    private string $publicPath = 'public';

    /**
     * The filename of the Vite manifest file.
     */
    private string $manifestFilename = 'manifest.json';

    /**
     * The key in the manifest file that contains the integrity hash. Set as
     * false to disable integrity checks.
     */
    private false|string $integrityKey = 'integrity';

    /**
     * Additional attributes to add to the script tags when generating.
     */
    private array $scriptTagAttributesResolvers = [];

    /**
     * Additional attributes to add to the style tags when generating.
     */
    private array $styleTagAttributesResolvers = [];

    /**
     *  Custom resolver for returning asset paths.
     *
     * @var callable(string, array): string|null
     */
    private $assetPathResolver;

    /**
     * Traverse the given entries and return as HTML script and link tags.
     *
     * @throws ViteException if manifest or entry cannot be found or manifest has
     *                       invalid json
     *
     * @todo Add support for @vite/client script tag when running HMR and passing of a string as a single entry.
     */
    public function __invoke(array $entries, ?string $buildDir = null): string
    {
        $buildDir ??= $this->buildDir;

        $manifest = $this->manifestContents($buildDir);

        $tags = [];

        foreach ($entries as $entry) {
            $chunk = $this->chunk($manifest, $entry);

            foreach ($chunk['imports'] ?? [] as $import) {
                foreach ($manifest[$import]['css'] ?? [] as $css) {
                    $partialManifest = array_filter(
                        $manifest,
                        fn (array $item) => isset($item['file']) && $item['file'] === $css
                    );

                    $tags[] = $this->makeTagForChunk(
                        array_key_first($partialManifest),
                        $this->assetPath("{$buildDir}/{$css}"),
                        reset($partialManifest),
                        $manifest
                    );
                }
            }

            $tags[] = $this->makeTagForChunk(
                $entry,
                $this->assetPath("{$buildDir}/{$chunk['file']}"),
                $chunk,
                $manifest
            );

            foreach ($chunk['css'] ?? [] as $css) {
                $partialManifest = array_filter(
                    $manifest,
                    fn (array $item) => isset($item['file']) && $item['file'] === $css
                );

                $tags[] = $this->makeTagForChunk(
                    array_key_first($partialManifest),
                    $this->assetPath("{$buildDir}/{$css}"),
                    reset($partialManifest),
                    $manifest
                );
            }
        }

        // Separate stylesheets and scripts from unique tags.
        [$stylesheets, $scripts] = partition(array_unique($tags), fn (string $tag) => str_starts_with($tag, '<link'));

        return implode('', $stylesheets) . implode('', $scripts);
    }

    /**
     * Set a custom resolver for returning asset paths. For example, Twig has an
     * asset() function that can be used to return the correct (public) path for
     * a given asset.
     */
    public function setAssetPathResolver(?callable $resolver): self
    {
        $this->assetPathResolver = $resolver;

        return $this;
    }

    /**
     * Return the public path for a given asset.
     *
     * @throws ViteException if manifest file cannot be found or has invalid json
     */
    public function asset(string $path, ?string $buildDir = null, array $context = []): string
    {
        $buildDir ??= $this->buildDir;

        if ($this->isRunningHot()) {
            return $this->hotAsset($path);
        }

        $chunk = $this->chunk($this->manifestContents($buildDir), $path);

        return $this->assetPath("{$buildDir}/{$chunk['file']}", $context);
    }

    /**
     * Determine if Vite is running in hot module replacement mode.
     */
    public function isRunningHot(): bool
    {
        return is_file($this->getHotFile());
    }

    /**
     * Get the path to the hot file.
     */
    public function getHotfile(): string
    {
        return $this->hotfile ?? "{$this->publicPath}/hot";
    }

    /**
     * Set the path to the hot file.
     */
    public function setHotfile(string $path): self
    {
        $this->hotfile = $path;

        return $this;
    }

    /**
     * Get the current nonce value.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Set a nonce value to be added to script and link tags. If null, a random
     * 40-character string will be generated.
     *
     * @throws RandomException if random string generation fails
     */
    public function setNonce(?string $value = null): void
    {
        $this->nonce = $value ?? randomStr(40);
    }

    /**
     * Set the name of the directory where Vite will store its manifest file
     * and any compiled assets.
     */
    public function setBuildDir(string $name): self
    {
        $this->buildDir = $name;

        return $this;
    }

    /**
     * Set the path to where public files, like static assets, are located.
     */
    public function setPublicPath(string $path): self
    {
        $this->publicPath = $path;

        return $this;
    }

    /**
     * Set the filename of the Vite manifest file.
     */
    public function setManifestFilename(string $value): self
    {
        $this->manifestFilename = $value;

        return $this;
    }

    /**
     * Set the key in the manifest file that contains the integrity hash. Set as
     * false to disable integrity checks.
     */
    public function setIntegrityKey(false|string $value): self
    {
        $this->integrityKey = $value;

        return $this;
    }

    /**
     * Set any additional attributes that should be added to script tags when
     * they are generated. This can be an associative array of attributes or a
     * callable that returns such an array.
     */
    public function setScriptTagAttributesResolvers(array|callable $attrs): self
    {
        if (! is_callable($attrs)) {
            $attrs = fn () => $attrs;
        }

        $this->scriptTagAttributesResolvers[] = $attrs;

        return $this;
    }

    /**
     * Set any additional attributes that should be added to link tags when they
     * are generated. This can be an associative array of attributes or a callable
     * that returns such an array.
     */
    public function setStyleTagAttributesResolvers(array|callable $attrs): self
    {
        if (! is_callable($attrs)) {
            $attrs = fn () => $attrs;
        }

        $this->styleTagAttributesResolvers[] = $attrs;

        return $this;
    }

    /**
     * Get the contents of the Vite manifest file.
     *
     * @throws ViteException if manifest cannot be found or has invalid json
     */
    private function manifestContents(?string $buildDir = null): array
    {
        $buildDir ??= $this->buildDir;

        $path = $this->manifestPath($buildDir);

        if (! is_file($path)) {
            throw new ViteException("Vite manifest not found at path: {$path}");
        }

        $manifest = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException("Invalid JSON in Vite manifest file at: {$path}");
        }

        return $manifest;
    }

    /**
     * Get the path to the Vite manifest file.
     */
    private function manifestPath(?string $buildDir = null): string
    {
        $buildDir ??= $this->buildDir;

        return "{$this->publicPath}/{$buildDir}/.vite/{$this->manifestFilename}";
    }

    /**
     * Get a specific entry and its elements from the Vite manifest.
     *
     * @throws ViteException if entry is cannot be found in manifest
     */
    private function chunk(array $manifest, string $entry): array
    {
        if (! isset($manifest[$entry])) {
            throw new ViteException("Unable to find entry in Vite manifest: {$entry}");
        }

        return $manifest[$entry];
    }

    /**
     * Generate a script or link tag for an entry chunk.
     */
    private function makeTagForChunk(string $src, string $url, ?array $chunk = null, ?array $manifest = null): string
    {
        if (
            $this->nonce === null
            && $this->integrityKey !== false
            && ! array_key_exists($this->integrityKey, $chunk ?? [])
            && $this->scriptTagAttributesResolvers === []
            && $this->styleTagAttributesResolvers === []) {
            return $this->isCssFile($url)
                ? $this->makeStylesheetTagWithAttributes($url, [])
                : $this->makeScriptTagWithAttributes($url, []);
        }

        if ($this->isCssFile($url)) {
            return $this->makeStylesheetTagWithAttributes(
                $url,
                $this->resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)
            );
        }

        return $this->makeScriptTagWithAttributes(
            $url,
            $this->resolveScriptTagAttributes($src, $url, $chunk, $manifest)
        );
    }

    /**
     * Check if the file at the given path is some form of CSS file.
     */
    private function isCssFile(string $path): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)(\?[^.]*)?$/', $path) === 1;
    }

    /**
     * Generate a link tag with attributes for the given URL.
     */
    private function makeStylesheetTagWithAttributes(string $url, array $attrs): string
    {
        $attrs = $this->parseAttributes(array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attrs));

        return '<link ' . implode(' ', $attrs) . ' />' . PHP_EOL;
    }

    /**
     * Parse the attributes into key="value" strings.
     */
    private function parseAttributes(array $attrs): array
    {
        $result = [];

        foreach ($attrs as $key => $value) {
            if ($value === false || $value === null) {
                continue;
            }
            if ($value === true) {
                $result[] = $key;
            } else {
                $result[] = $key . '="' . $value . '"';
            }
        }

        return $result;
    }

    /**
     * Generate a script tag with attributes for the given URL.
     */
    private function makeScriptTagWithAttributes(string $url, array $attrs): string
    {
        $attrs = $this->parseAttributes(array_merge([
            'type' => 'module',
            'src' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attrs));

        return '<script ' . implode(' ', $attrs) . '></script>' . PHP_EOL;
    }

    /**
     * Resolve any additional attributes for the chunk's generated link tag.
     */
    private function resolveStylesheetTagAttributes(
        string $src,
        string $url,
        ?array $chunk = null,
        ?array $manifest = null
    ): array {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->styleTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Resolve any additional attributes for the chunk's generated script tag.
     */
    private function resolveScriptTagAttributes(
        string $src,
        string $url,
        ?array $chunk = null,
        ?array $manifest = null
    ): array {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->scriptTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Resolve the asset path using the configured resolver or fallback.
     */
    private function assetPath(string $path, array $context = []): string
    {
        return $this->assetPathResolver !== null
            ? ($this->assetPathResolver)($path, $context)
            : $path;
    }

    /**
     * Get the path to an asset when Vite is in hot module replacement mode.
     */
    private function hotAsset(string $path): string
    {
        return rtrim(file_get_contents($this->getHotfile())) . '/' . $path;
    }
}
