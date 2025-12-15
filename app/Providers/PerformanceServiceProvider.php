<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

final class PerformanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('performance.per_page_resolver', function () {
            $default = (int) config('performance.pagination.default_per_page', 25);
            $max = (int) config('performance.pagination.max_per_page', 100);

            return static function (?int $perPage) use ($default, $max): int {
                if ($max < 1) {
                    return $default;
                }

                $resolved = $perPage ?? $default;

                if ($resolved < 1) {
                    return $default;
                }

                if ($resolved > $max) {
                    return $max;
                }

                return $resolved;
            };
        });
    }

    public function boot(): void
    {
        $this->configureLazyLoading();
        $this->registerPaginationMacros();
        $this->registerSlowQueryLogging();
    }

    private function configureLazyLoading(): void
    {
        $preventLazyLoading = (bool) config('performance.lazy_loading.prevent', false);
        $strictMode = (bool) config('performance.lazy_loading.strict_mode', false);

        if ($strictMode) {
            Model::shouldBeStrict();

            return;
        }

        if ($preventLazyLoading) {
            Model::preventLazyLoading(true);
        }
    }

    private function registerPaginationMacros(): void
    {
        Builder::macro('safePaginate', function (?int $perPage = null, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null) {
            $resolvePerPage = app('performance.per_page_resolver');
            $resolvedPerPage = $resolvePerPage($perPage);

            return $this->paginate($resolvedPerPage, $columns, $pageName, $page);
        });

        Builder::macro('safeSimplePaginate', function (?int $perPage = null, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null) {
            $resolvePerPage = app('performance.per_page_resolver');
            $resolvedPerPage = $resolvePerPage($perPage);

            return $this->simplePaginate($resolvedPerPage, $columns, $pageName, $page);
        });
    }

    private function registerSlowQueryLogging(): void
    {
        $thresholdMs = config('performance.query.slow_query_threshold_ms');

        if (! is_numeric($thresholdMs) || (int) $thresholdMs <= 0) {
            return;
        }

        DB::whenQueryingForLongerThan(((int) $thresholdMs) / 1000, function ($connection, QueryExecuted $event) use ($thresholdMs): void {
            Log::channel('slow_queries')->warning('Slow query detected', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time_ms' => $event->time,
                'threshold_ms' => (int) $thresholdMs,
                'connection' => $connection->getName(),
            ]);
        });
    }
}
