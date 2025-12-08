<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Mateffy\Introspect\DTO\Model;
use Mateffy\Introspect\DTO\ModelProperty;
use Mateffy\Introspect\DTO\Route as RouteDto;
use Mateffy\Introspect\LaravelIntrospect;

final readonly class CodebaseIntrospectionService
{
    public function __construct(
        private LaravelIntrospect $introspect,
        private CacheRepository $cache,
    ) {}

    /**
     * Build a snapshot of the codebase using configured directories.
     *
     * @return array<string, mixed>
     */
    public function snapshot(bool $forceRefresh = false): array
    {
        $cacheKey = config('introspect.cache.key', 'introspect.snapshot');
        $ttl = (int) config('introspect.cache.ttl', 0);

        if ($forceRefresh) {
            $this->cache->forget($cacheKey);
        }

        $buildSnapshot = fn (): array => [
            'generated_at' => now()->toIso8601String(),
            'directories' => config('introspect.directories', LaravelIntrospect::DEFAULT_DIRECTORIES),
            'views' => $this->views(),
            'routes' => $this->routes(),
            'classes' => $this->classes(),
            'models' => $this->models(),
        ];

        if ($ttl <= 0) {
            return $buildSnapshot();
        }

        return $this->cache->remember($cacheKey, $ttl, $buildSnapshot);
    }

    /**
     * @return list<string>
     */
    public function views(): array
    {
        return $this->introspect
            ->views()
            ->get()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function routes(): array
    {
        return $this->introspect
            ->routes()
            ->get()
            ->map(fn (Route $route): array => RouteDto::fromRoute($route)->toArray())
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function classes(): array
    {
        $namespaces = config('introspect.namespaces', []);

        return $this->introspect
            ->classes()
            ->get()
            ->filter(fn (string $class): bool => $this->matchesNamespace($class, $namespaces))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function models(): array
    {
        $namespaces = config('introspect.namespaces', []);

        return $this->introspect
            ->models()
            ->get()
            ->filter(fn (Model $model): bool => $this->matchesNamespace($model->classpath, $namespaces))
            ->map(fn (Model $model): array => [
                'classpath' => $model->classpath,
                'description' => $model->description,
                'schema' => $model->schema(),
                'properties' => $this->mapProperties($model->properties),
            ])
            ->values()
            ->all();
    }

    public function flushCache(): void
    {
        $this->cache->forget(config('introspect.cache.key', 'introspect.snapshot'));
    }

    /**
     * @param  list<string>  $namespaces
     */
    private function matchesNamespace(string $class, array $namespaces): bool
    {
        if ($namespaces === []) {
            return true;
        }

        return array_any($namespaces, fn ($namespace): bool => str_starts_with($class, $namespace));
    }

    /**
     * @param  Collection<string, ModelProperty>  $properties
     * @return list<array<string, mixed>>
     */
    private function mapProperties(Collection $properties): array
    {
        return $properties
            ->values()
            ->map(fn (ModelProperty $property): array => [
                'name' => $property->name,
                'description' => $property->description,
                'types' => $property->types->values()->all(),
                'default' => $property->default,
                'readable' => $property->readable,
                'writable' => $property->writable,
                'fillable' => $property->fillable,
                'hidden' => $property->hidden,
                'appended' => $property->appended,
                'relation' => $property->relation,
                'cast' => $property->cast,
            ])
            ->all();
    }
}
