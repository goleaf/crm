<?php

declare(strict_types=1);

namespace Spatie\PestPluginRouteTest;

use function test;

use Tests\Support\RouteTesting\RouteTestingBuilder;

function routeTesting(): RouteTestingBuilder
{
    /** @var \Pest\Support\HigherOrderTapProxy $proxy */
    $proxy = test();

    /** @var \Tests\TestCase $testCase */
    $testCase = $proxy->target;

    return new RouteTestingBuilder($testCase);
}
