<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * Not a required CI check. A dry-run of these sets on 10 November 2026
 * wanted edits in 28 files, including AssertSeeToAssertSeeHtmlRector on the
 * plain-text /metrics response. Apply that migration only after review.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
    ])
    ->withPhpSets(php85: true)
    ->withComposerBased(laravel: true);
