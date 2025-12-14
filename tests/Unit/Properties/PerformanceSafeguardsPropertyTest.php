<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Http\Middleware\EnforcePaginationLimits;
use App\Models\Company;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * **Feature: system-technical, Property 2: Performance safeguards**
 *
 * Validates Requirements 2.1 and 2.2 by ensuring pagination/result limits
 * are enforced to prevent runaway queries.
 */
final class PerformanceSafeguardsPropertyTest extends TestCase
{
    public function test_safe_paginate_caps_per_page_at_configured_maximum(): void
    {
        Company::factory()->count(60)->create();

        $pagination = Company::query()->safePaginate(250);

        $this->assertEquals(
            config('performance.pagination.max_per_page'),
            $pagination->perPage(),
        );
    }

    public function test_safe_paginate_uses_default_when_value_is_missing_or_invalid(): void
    {
        Company::factory()->count(10)->create();
        $defaultPerPage = config('performance.pagination.default_per_page');

        $paginationWithNull = Company::query()->safePaginate(null);
        $paginationWithZero = Company::query()->safePaginate(0);

        $this->assertEquals($defaultPerPage, $paginationWithNull->perPage());
        $this->assertEquals($defaultPerPage, $paginationWithZero->perPage());
    }

    public function test_middleware_clamps_per_page_parameter_to_configured_bounds(): void
    {
        $middleware = new EnforcePaginationLimits();
        $parameter = config('performance.pagination.parameter');
        $max = (int) config('performance.pagination.max_per_page');

        $request = Request::create('/dummy', 'GET', [
            $parameter => $max + 50,
        ]);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $middleware->handle($request, function (Request $handledRequest) use ($parameter) {
            return response()->json([
                'per_page' => $handledRequest->input($parameter),
            ]);
        });

        $this->assertSame($max, $response->getData(true)['per_page']);
    }
}
