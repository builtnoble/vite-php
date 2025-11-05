<?php

declare(strict_types=1);

use Builtnoble\VitePHP\ViteException;
use Random\RandomException;

if (! function_exists('partition')) {
    /**
     * Partitions an array into two arrays based on a callback function.
     *
     * @param array    $array    the array to partition
     * @param callable $callback The callback function to determine the partitioning.
     *                           It should return true or false.
     *
     * @return array an array containing two arrays: the first with elements
     *               for which the callback returned true, and the second with
     *               elements for which it returned false
     */
    function partition(array $array, callable $callback): array
    {
        $true = [];
        $false = [];

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                $true[$key] = $value;
            } else {
                $false[$key] = $value;
            }
        }

        return [$true, $false];
    }
}

if (! function_exists('randomStr')) {
    /**
     * Generates a random string of the specified length.
     *
     * @throws RandomException if an appropriate source of randomness cannot be found
     */
    function randomStr(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes((int) ceil($size / 3) * 3);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}

if (! function_exists('normalizeResolvers')) {
    /**
     * Normalize a resolver option into an array of resolvers.
     *
     * Accepts a single callable, an array of callables, or an array of attribute arrays.
     *
     * @throws ViteException
     */
    function normalizeResolvers(mixed $value): array
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
