<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        codeQuality: true,
        deadCode: true,
        instanceOf: true,
        privatization: true,
        typeDeclarations: true,
    )
    ->withPhpSets(php85: true);
