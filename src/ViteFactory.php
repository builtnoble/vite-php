<?php

declare(strict_types=1);

namespace Builtnoble\VitePHP;

use Random\RandomException;

final class ViteFactory
{
    /**
     * Build and configure a Vite instance.
     *
     * Options:
     *  - assetPathResolver: callable|null
     *  - scriptTagAttributesResolvers: callable|array
     *  - styleTagAttributesResolvers: callable|array
     *  - nonce: string|null (pass null to generate a random nonce)
     *  - hotfile: string
     *  - buildDir: string
     *  - publicDir: string
     *  - manifestFilename: string
     *  - integrityKey: string|false
     *
     * @throws RandomException if nonce generation is requested and fails
     * @throws ViteException
     */
    public static function make(array $options = [], ?callable $creator = null): ViteInterface
    {
        $vite = $creator ? $creator() : new Vite();

        if (! $vite instanceof ViteInterface) {
            throw new ViteException('The creator callable must return an instance of ' . ViteInterface::class);
        }

        if (array_key_exists('assetPathResolver', $options)) {
            $vite->setAssetPathResolver($options['assetPathResolver']);
        }

        if (array_key_exists('hotfile', $options)) {
            $vite->setHotfile((string) $options['hotfile']);
        }

        if (array_key_exists('buildDir', $options)) {
            $vite->setBuildDir((string) $options['buildDir']);
        }

        if (array_key_exists('publicDir', $options)) {
            $vite->setPublicPath((string) $options['publicDir']);
        }

        if (array_key_exists('manifestFilename', $options)) {
            $vite->setManifestFilename((string) $options['manifestFilename']);
        }

        if (array_key_exists('integrityKey', $options)) {
            $vite->setIntegrityKey($options['integrityKey']);
        }

        if (array_key_exists('nonce', $options)) {
            // may throw RandomException if null is passed (to generate)
            $vite->setNonce($options['nonce']);
        }

        if (array_key_exists('scriptTagAttributesResolvers', $options)) {
            foreach (self::normalizeResolvers($options['scriptTagAttributesResolvers']) as $resolver) {
                $vite->setScriptTagAttributesResolvers($resolver);
            }
        }

        if (array_key_exists('styleTagAttributesResolvers', $options)) {
            foreach (self::normalizeResolvers($options['styleTagAttributesResolvers']) as $resolver) {
                $vite->setStyleTagAttributesResolvers($resolver);
            }
        }

        return $vite;
    }

    /**
     * Normalize a resolver option into an array of resolvers.
     *
     * Accepts a single callable, an array of callables, or an array of attribute arrays.
     */
    private static function normalizeResolvers(mixed $value): array
    {
        if (is_callable($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            throw new ViteException('Resolver option must be a callable or an array of callables/attribute arrays.');
        }

        $values = array_values($value);

        foreach ($values as $v) {
            if (! is_callable($v) && ! is_array($v)) {
                throw new ViteException('Each resolver array value must be a callable or an attribute array.');
            }
        }

        return $values;
    }
}
