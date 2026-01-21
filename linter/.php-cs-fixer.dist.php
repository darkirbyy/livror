<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/../src')
    ->in(__DIR__.'/../tests')
;


return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'single_line_empty_body' => true,
        'function_declaration' => [
            'closure_fn_spacing' => 'none'
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile("var/cache/linter/.php-cs-fixer.cache")
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;