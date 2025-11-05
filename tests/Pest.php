<?php

use Builtnoble\VitePHP\Tests\TestCase;

pest()->project()->github('builtnoble/vite-php');

pest()->group('feature')->in('Feature')->extend(TestCase::class);
pest()->group('unit')->in('Unit');
