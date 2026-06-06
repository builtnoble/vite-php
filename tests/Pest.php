<?php

declare(strict_types=1);

use Builtnoble\VitePHP\Tests\TestCase;

pest()->project()->github('builtnoble/vite-php');

pest()->group('feature')->in('Feature')->extend(TestCase::class);
pest()->group('unit')->in('Unit');
