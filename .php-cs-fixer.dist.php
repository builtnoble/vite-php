<?php

use PhpCsFixer\{Config, Finder};

$config = new Config();
$finder = Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return $config->setRules([
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    'assign_null_coalescing_to_coalesce_equal' => true,
    'concat_space' => ['spacing' => 'one'],
    'global_namespace_import' => ['import_classes' => true],
    'group_import' => true,
    'heredoc_indentation' => true,
    'increment_style' => ['style' => 'post'],
    'multiline_promoted_properties' => true,
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    'new_expression_parentheses' => true,
    'not_operator_with_successor_space' => true,
    'octal_notation' => true,
    'ordered_attributes' => true,
    'ordered_interfaces' => true,
    'phpdoc_to_comment' => ['allow_before_return_statement' => true, 'ignored_tags' => ['todo']],
    'simplified_if_return' => true,
    'simplified_null_return' => true,
    'single_import_per_statement' => false,
    'ternary_to_null_coalescing' => true,
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
])->setFinder($finder);
