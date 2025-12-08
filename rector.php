<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\FunctionLike\FunctionLikeToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_TESTING,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/app-modules',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/lang',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        RemoveUnusedPrivateMethodRector::class => [
            // Skip Filament importer lifecycle hooks - they're called dynamically via callHook()
            __DIR__.'/app/Filament/Imports/*',
        ],
        PrivatizeFinalClassMethodRector::class => [
            // Filament expects protected visibility for lifecycle hooks
            __DIR__.'/app/Filament/Imports/*',
        ],
        FirstClassCallableRector::class => [
            // class_exists has optional bool param that conflicts with Collection::first signature
            __DIR__.'/app/Providers/AppServiceProvider.php',
        ],
        FunctionLikeToFirstClassCallableRector::class => [
            // class_exists has optional bool param that conflicts with Collection::first signature
            __DIR__.'/app/Providers/AppServiceProvider.php',
        ],
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
        'dd',
        'ddd',
        'dump',
        'ray',
        'var_dump',
    ])
    ->withPhpSets();
