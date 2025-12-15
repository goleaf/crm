<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnforceIpLists;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class EnforceIpListsTest extends TestCase
{
    public function test_allows_request_when_no_lists_configured(): void
    {
        config([
            'laravel-crm.security.ip_whitelist' => [],
            'laravel-crm.security.ip_denylist' => [],
        ]);

        $middleware = new EnforceIpLists;
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_blocks_request_when_ip_is_in_denylist(): void
    {
        config([
            'laravel-crm.security.ip_whitelist' => [],
            'laravel-crm.security.ip_denylist' => ['203.0.113.10'],
        ]);

        $middleware = new EnforceIpLists;
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected request to be blocked by denylist.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_blocks_request_when_allowlist_is_set_and_ip_not_listed(): void
    {
        config([
            'laravel-crm.security.ip_whitelist' => ['10.0.0.0/8'],
            'laravel-crm.security.ip_denylist' => [],
        ]);

        $middleware = new EnforceIpLists;
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected request to be blocked when IP is not in allowlist.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_allows_request_when_ip_matches_allowlist_cidr(): void
    {
        config([
            'laravel-crm.security.ip_whitelist' => ['10.0.0.0/8'],
            'laravel-crm.security.ip_denylist' => [],
        ]);

        $middleware = new EnforceIpLists;
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.12.34.56']);

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_denylist_takes_precedence_over_allowlist(): void
    {
        config([
            'laravel-crm.security.ip_whitelist' => ['10.0.0.0/8'],
            'laravel-crm.security.ip_denylist' => ['10.12.34.56'],
        ]);

        $middleware = new EnforceIpLists;
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.12.34.56']);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected request to be blocked by denylist even when allowlisted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
