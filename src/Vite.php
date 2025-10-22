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
     * The directory where the build assets are located.
     */
    private string $buildDir = 'build';

    /**
     * The directory where the index.php or static assets are located.
     */
    private string $publicDir = 'public';

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
     * List of additional attributes to add to the script tags when generating
     * script tags for assets.
     */
    private array $scriptTagAttributesResolvers = [];

    /**
     * List of additional attributes to add to the style tags when generating
     * style tags for assets.
     */
    private array $styleTagAttributesResolvers = [];

    /** @var null|callable(string, array): string */
    private $assetPathResolver;

    /**
     * Traverse the vite manifest and generate HTML tags for the given entries.
     *
     * @throws ViteException
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
     * Resolve asset paths using the given callable.
     */
    public function setAssetPathResolver(?callable $resolver): self
    {
        $this->assetPathResolver = $resolver;

        return $this;
    }

    /**
     * Get the path to a Vite asset.
     *
     * @throws ViteException
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
     * Determine if the HMR server is running.
     */
    public function isRunningHot(): bool
    {
        return is_file($this->getHotFile());
    }

    public function getHotfile(): string
    {
        return $this->hotfile ?? "{$this->publicDir}/hot";
    }

    public function setHotfile(string $path): self
    {
        $this->hotfile = $path;

        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * @throws RandomException
     */
    public function setNonce(?string $nonce = null): void
    {
        $this->nonce = $nonce ?? randomStr(40);
    }

    public function setBuildDir(string $path): self
    {
        $this->buildDir = $path;

        return $this;
    }

    public function setPublicDir(string $publicDir): self
    {
        $this->publicDir = $publicDir;

        return $this;
    }

    public function setManifestFilename(string $manifestFilename): self
    {
        $this->manifestFilename = $manifestFilename;

        return $this;
    }

    public function setIntegrityKey(false|string $key): self
    {
        $this->integrityKey = $key;

        return $this;
    }

    /**
     * Set a callback to resolve attributes for script tags.
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
     * Set a callback to resolve attributes for style tags.
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
     * @throws ViteException
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

        return "{$this->publicDir}/{$buildDir}/.vite/{$this->manifestFilename}";
    }

    /**
     * Get a specific entry from the Vite manifest.
     *
     * @throws ViteException
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
    private function makeStylesheetTagWithAttributes(string $url, array $attributes): string
    {
        $attributes = $this->parseAttributes(array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<link ' . implode(' ', $attributes) . ' />' . PHP_EOL;
    }

    /**
     * Parse the attributes into key="value" strings.
     */
    private function parseAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $key => $value) {
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
    private function makeScriptTagWithAttributes(string $url, array $attributes): string
    {
        $attributes = $this->parseAttributes(array_merge([
            'type' => 'module',
            'src' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<script ' . implode(' ', $attributes) . '></script>' . PHP_EOL;
    }

    /**
     * Resolve the attributes for the chunks generated stylesheet tag.
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
     * Resolve the attributes for the chunks generated script tag.
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
     * Get the path to an asset when the HMR server is running.
     */
    private function hotAsset(string $path): string
    {
        return rtrim(file_get_contents($this->getHotfile())) . '/' . $path;
    }
}
