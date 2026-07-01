<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\ValueObject\PhpVersion;
use Rector\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        StrContainsRector::class => [
            __DIR__.'/app/Http/Controllers/LanguageController.php',
        ],
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withRules([
        ExplicitPublicClassMethodRector::class,
        RemoveUnusedVariableInCatchRector::class,
        StrContainsRector::class,
    ]);
