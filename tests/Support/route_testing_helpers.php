<?php

declare(strict_types=1);

namespace Spatie\PestPluginRouteTest;

use Tests\Support\RouteTesting\RouteTestingBuilder;

use function test;

function routeTesting(): RouteTestingBuilder
{
    /** @var \Pest\Support\HigherOrderTapProxy $proxy */
    $proxy = test();

    /** @var \Tests\TestCase $testCase */
    $testCase = $proxy->target;

    return new RouteTestingBuilder($testCase);
}

