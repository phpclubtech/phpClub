<?php
use PhpCsFixer\Finder;
use PhpCsFixer\Config;
return Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'yoda_style' => false,
        'concat_space' => false,
        'ordered_imports' => true,
        'single_import_per_statement' => false,
        'blank_line_before_statement' => false
    ])
     ->setFinder(
            Finder::create()->in([
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ])
        )
    ->setRiskyAllowed(true)
;