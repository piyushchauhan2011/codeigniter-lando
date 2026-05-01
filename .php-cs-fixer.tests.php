<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/** @var Config $config */
$config = require __DIR__ . '/.php-cs-fixer.dist.php';

$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/tests',
    ]);

$overrides = [
    'declare_strict_types'                   => true,
    'header_comment'                         => false,
    'php_unit_internal_class'                => false,
    'phpdoc_to_return_type'                  => true,
    'void_return'                            => true,
    'php_unit_test_case_static_method_calls' => false,
];

return $config
    ->setFinder($finder)
    ->setCacheFile('build/.php-cs-fixer.tests.cache')
    ->setRules(array_merge($config->getRules(), $overrides));
