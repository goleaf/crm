<?php

declare(strict_types=1);

namespace Tests\Support\RouteTesting;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

final class RouteTestingBuilder
{
    /** @var array<int, string> */
    private array $only = [];

    /** @var array<int, string> */
    private array $except = [];

    /** @var array<string, mixed> */
    private array $bindings = [];

    private ?Authenticatable $actingAs = null;

    private ?string $token = null;

    public function __construct(
        private readonly TestCase $testCase,
    ) {
    }

    /**
     * @param  array<int, string>  $routeNamesOrPatterns
     */
    public function only(array $routeNamesOrPatterns): self
    {
        $this->only = $routeNamesOrPatterns;

        return $this;
    }

    /**
     * @param  array<int, string>  $routeNamesOrPatterns
     */
    public function except(array $routeNamesOrPatterns): self
    {
        $this->except = array_values(array_merge($this->except, $routeNamesOrPatterns));

        return $this;
    }

    public function actingAs(Authenticatable $user, ?string $guard = null): self
    {
        $this->actingAs = $user;

        if ($guard !== null) {
            $this->testCase->actingAs($user, $guard);
        }

        return $this;
    }

    public function withToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function bind(string $key, mixed $value): self
    {
        $this->bindings[$key] = $value;

        return $this;
    }

    public function assertAllRoutesAreAccessible(): void
    {
        $routes = $this->resolvedRoutes();

        foreach ($routes as $routeName => $route) {
            $response = $this->makeRequest($routeName, $route);

            if ($response === null) {
                continue;
            }

            try {
                $response->assertSuccessful();
            } catch (\Throwable) {
                Assert::fail("Route '{$routeName}' expected 2xx, got {$response->status()}.");
            }
        }
    }

    public function assertAllRoutesRedirect(): void
    {
        $routes = $this->resolvedRoutes();

        foreach ($routes as $routeName => $route) {
            $response = $this->makeRequest($routeName, $route);

            if ($response === null) {
                continue;
            }

            try {
                $response->assertRedirect();
            } catch (\Throwable) {
                Assert::fail("Route '{$routeName}' expected redirect, got {$response->status()}.");
            }
        }
    }

    /**
     * @param  callable(TestResponse, string): void  $assertion
     */
    public function assertAllRoutesReturn(callable $assertion): void
    {
        $routes = $this->resolvedRoutes();

        foreach ($routes as $routeName => $route) {
            $response = $this->makeRequest($routeName, $route);

            if ($response === null) {
                continue;
            }

            $assertion($response, $routeName);
        }
    }

    /**
     * @return array<string, LaravelRoute>
     */
    private function resolvedRoutes(): array
    {
        $routes = $this->allNamedRoutes();

        if ($this->only !== []) {
            $routes = $routes->filter(fn (LaravelRoute $route, string $name): bool => $this->matchesAny($name, $this->only));
        }

        if ($this->except !== []) {
            $routes = $routes->reject(fn (LaravelRoute $route, string $name): bool => $this->matchesAny($name, $this->except));
        }

        if ($routes->isEmpty()) {
            Assert::fail('No routes matched the given criteria.');
        }

        /** @var array<string, LaravelRoute> $result */
        $result = $routes->all();

        return $result;
    }

    /**
     * @return Collection<string, LaravelRoute>
     */
    private function allNamedRoutes(): Collection
    {
        /** @var Collection<string, LaravelRoute> $routes */
        $routes = collect(Route::getRoutes())
            ->filter(fn (LaravelRoute $route): bool => $route->getName() !== null)
            ->mapWithKeys(fn (LaravelRoute $route): array => [(string) $route->getName() => $route]);

        return $routes;
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function matchesAny(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private function isExplicitlyIncluded(string $routeName): bool
    {
        foreach ($this->only as $pattern) {
            if (! str_contains($pattern, '*') && $pattern === $routeName) {
                return true;
            }
        }

        return false;
    }

    private function makeRequest(string $routeName, LaravelRoute $route): ?TestResponse
    {
        $method = $this->pickMethod($route);

        if ($method === null) {
            if ($this->isExplicitlyIncluded($routeName)) {
                Assert::fail("Route '{$routeName}' has no supported HTTP methods for testing.");
            }

            return null;
        }

        try {
            $url = route($routeName, $this->bindings);
        } catch (UrlGenerationException $exception) {
            if ($this->isExplicitlyIncluded($routeName)) {
                Assert::fail("Missing parameters for route '{$routeName}': {$exception->getMessage()}");
            }

            return null;
        }

        if ($this->actingAs !== null) {
            $this->testCase->actingAs($this->actingAs);
        }

        $headers = $this->headers();

        if ($this->isApiRoute($route)) {
            return $this->makeApiRequest($method, $url, $headers);
        }

        return $this->makeWebRequest($method, $url, $headers);
    }

    private function pickMethod(LaravelRoute $route): ?string
    {
        $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));

        if (in_array('GET', $methods, true)) {
            return 'GET';
        }

        return $methods[0] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        if ($this->token === null) {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    private function isApiRoute(LaravelRoute $route): bool
    {
        return str_starts_with($route->uri(), 'api/');
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function makeApiRequest(string $method, string $url, array $headers): TestResponse
    {
        return match ($method) {
            'GET' => $this->testCase->getJson($url, $headers),
            'POST' => $this->testCase->postJson($url, [], $headers),
            'PUT', 'PATCH', 'DELETE' => $this->testCase->json($method, $url, [], $headers),
            default => $this->testCase->json($method, $url, [], $headers),
        };
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function makeWebRequest(string $method, string $url, array $headers): TestResponse
    {
        return match ($method) {
            'GET' => $this->testCase->get($url, $headers),
            'POST' => $this->testCase->post($url, [], $headers),
            'PUT' => $this->testCase->put($url, [], $headers),
            'PATCH' => $this->testCase->patch($url, [], $headers),
            'DELETE' => $this->testCase->delete($url, [], $headers),
            default => $this->testCase->call($method, $url, [], [], [], $headers),
        };
    }
}

