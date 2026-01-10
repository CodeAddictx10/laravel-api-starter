<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,

        // Laravel- and readability-friendly rules
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'for', 'foreach', 'while'],
        ],
        'single_quote' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'no_trailing_comma_in_singleline' => true,
        'not_operator_with_successor_space' => true,

        // Cleaner class structure
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'property' => 'one',
                'method' => 'one',
            ],
        ],

        // Control spaces & alignment
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => ['default' => 'single_space'],

        // PHP 8+ rules
        'modernize_types_casting' => true,
        'declare_strict_types' => false, // change to true if your project uses strict types
        'fully_qualified_strict_types' => true,

        // Risky but helpful rules
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder);
