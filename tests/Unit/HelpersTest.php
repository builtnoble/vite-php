<?php

declare(strict_types=1);

use Builtnoble\VitePHP\ViteException;

describe('partition', function () {
    dataset('partition_dataset', [
        'mixed keys' => [
            ['a' => 1, 'b' => 2, 'c' => 3, 4 => 4],
            ['b' => 2, 4 => 4],
            ['a' => 1, 'c' => 3],
        ],
        'all evens' => [
            [0 => 2, 1 => 4, 'x' => 6],
            [0 => 2, 1 => 4, 'x' => 6],
            [],
        ],
        'all odds' => [
            ['a' => 1, 'b' => 3, 2 => 5],
            [],
            ['a' => 1, 'b' => 3, 2 => 5],
        ],
        'empty' => [
            [],
            [],
            [],
        ],
    ]);

    it(
        'splits array into true and false groups preserving keys',
        function (array $data, array $expectedEvens, array $expectedOdds) {
            [$evens, $odds] = partition($data, fn ($value, $key) => $value % 2 === 0);

            expect($evens)->toEqual($expectedEvens)
                ->and($odds)->toEqual($expectedOdds);
        }
    )->with('partition_dataset');
});

describe('randomStr', function () {
    dataset('random_length_dataset', [
        'len_1' => [1],
        'len_16' => [16],
        'len_50' => [50],
        'len_32' => [32],
    ]);

    it(
        'returns string of requested length with alphanumeric chars',
        function (int $length) {
            $str = randomStr($length);

            expect($str)->toBeString()
                ->and(strlen($str))->toBe($length)
                ->and($str)->toMatch('/^[A-Za-z0-9]+$/');
        }
    )->with('random_length_dataset');

    it('returns different values across calls', function (int $length) {
        // For very short lengths, we need to try multiple times due to limited character space
        if ($length === 1) {
            $values = [];
            for ($i = 0; $i < 10; $i++) {
                $values[] = randomStr($length);
            }

            // With 10 attempts and 62 possible characters, we should get at least 2 different values
            expect(count(array_unique($values)))->toBeGreaterThan(1);
        } else {
            $a = randomStr($length);
            $b = randomStr($length);

            expect($a)->not->toBe($b);
        }
    })->with('random_length_dataset');
});

describe('normalizeResolvers', function () {
    it('wraps a single callable into an array', function () {
        $callable = fn (): array => ['defer' => true];

        $result = normalizeResolvers($callable);

        expect($result)->toBeArray()
            ->and(count($result))->toBe(1)
            ->and(is_callable($result[0]))->toBeTrue()
            ->and($result[0]())->toEqual(['defer' => true]);
    });

    it('accepts an array of callables and reindexes keys', function () {
        $a = fn (): array => ['a' => 1];
        $b = fn (): array => ['b' => 2];

        $input = ['first' => $a, 'second' => $b];

        $result = normalizeResolvers($input);

        expect(array_keys($result))->toBe([0, 1])
            ->and($result[0])->toBe($a)
            ->and($result[1])->toBe($b);
    });

    it('accepts an associative array of attribute arrays and preserves order while reindexing', function () {
        $a = ['defer' => true];
        $b = ['media' => 'all'];

        $input = ['first' => $a, 'second' => $b];

        $result = normalizeResolvers($input);

        expect(array_keys($result))->toBe([0, 1])
            ->and($result[0])->toBe($a)
            ->and($result[1])->toBe($b);
    });

    it('accepts mixed callables and attribute arrays and reindexes', function () {
        $a = ['defer' => true];
        $b = fn (): array => ['media' => 'all'];

        $input = ['one' => $a, 'two' => $b];

        $result = normalizeResolvers($input);

        expect(array_keys($result))->toBe([0, 1])
            ->and($result[0])->toBe($a)
            ->and($result[1])->toBe($b);
    });

    it('returns an empty array for an empty input array', function () {
        $result = normalizeResolvers([]);

        expect($result)->toBeArray()
            ->and(count($result))->toBe(0);
    });

    it('throws ViteException for non-callable non-array values', function () {
        expect(fn () => normalizeResolvers('not-callable-or-array'))->toThrow(ViteException::class);
    });

    it('throws ViteException when an array contains invalid element types', function () {
        $input = [
            'valid' => ['defer' => true],
            'invalid' => 'string-value',
        ];

        expect(fn () => normalizeResolvers($input))->toThrow(ViteException::class);
    });
});
