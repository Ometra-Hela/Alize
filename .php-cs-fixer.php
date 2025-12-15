<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/config')
    ->in(__DIR__.'/database')
    ->in(__DIR__.'/routes');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => false,
        'single_blank_line_at_eof' => true,
        'class_attributes_separation' => [
            'elements' => ['const' => 'one', 'property' => 'one', 'method' => 'one'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'throw'],
        ],
    ])
    ->setFinder($finder);
