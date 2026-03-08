<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        'array_syntax' => ['syntax' => 'short'],
        'strict_param' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
