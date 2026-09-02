<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withRootFiles()
    ->withConfiguredRule(
        PhpCsFixer\Fixer\Import\OrderedImportsFixer::class,
        [
            'imports_order' => ['class', 'function', 'const'],
        ],
    )
    ->withPhpCsFixerSets(perCS20: true)
    ->withPreparedSets(psr12: true, common: true, cleanCode: true);
