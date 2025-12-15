<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use Carbon\Carbon;
use Eliseekn\LaravelMetrics\Enums\Period;
use Eliseekn\LaravelMetrics\LaravelMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class LaravelMetricsHelper
{
    /**
     * Build month-over-month counts for chart widgets.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function monthlyCountTrend(Builder|QueryBuilder $query, string $dateColumn = 'created_at', int $months = 6): array
    {
        $metrics = LaravelMetrics::query($query)
            ->dateColumn($dateColumn)
            ->countByMonth(count: 0)
            ->forYear(self::now()->year)
            ->trends();

        return self::fillMissingMonths($metrics, $months);
    }

    /**
     * Build month-over-month sums for chart widgets.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function monthlySumTrend(Builder|QueryBuilder $query, string $column, string $dateColumn = 'created_at', int $months = 6): array
    {
        $metrics = LaravelMetrics::query($query)
            ->dateColumn($dateColumn)
            ->sumByMonth($column, count: 0)
            ->forYear(self::now()->year)
            ->trends();

        return self::fillMissingMonths($metrics, $months);
    }

    /**
     * Get the current period total with variations against the previous period.
     *
     * @return array{count: int, variation: array<string, int|string>|array{}}
     */
    public static function currentMonthTotalWithVariation(
        Builder|QueryBuilder $query,
        string $column = 'id',
        string $dateColumn = 'created_at',
        int $previousMonths = 1,
        bool $inPercent = false,
    ): array {
        $metrics = LaravelMetrics::query($query)
            ->dateColumn($dateColumn)
            ->countByMonth(count: 0)
            ->forYear(self::now()->year)
            ->trends();

        $map = self::trendMap($metrics);

        $currentLabel = self::now()->isoFormat('MMMM');
        $previousLabel = self::now()->copy()->subMonths($previousMonths)->isoFormat('MMMM');

        $current = self::asInt($map[$currentLabel] ?? 0);
        $previous = self::asInt($map[$previousLabel] ?? 0);

        $result = [
            'count' => $current,
            'variation' => [],
        ];

        $delta = $current - $previous;

        if ($delta !== 0 && $previous > 0) {
            $value = $inPercent
                ? (abs($delta) / $previous) * 100
                : abs($delta);

            $result['variation'] = [
                'type' => $delta > 0 ? 'increase' : 'decrease',
                'value' => $value,
            ];
        }

        return $result;
    }

    private static function fillMissingMonths(array $trend, int $months): array
    {
        $labels = self::recentMonthLabels($months);
        $map = self::trendMap($trend);

        return [
            'labels' => $labels,
            'data' => array_map(
                static fn (string $label): int => self::asInt($map[$label] ?? 0),
                $labels,
            ),
        ];
    }

    /**
     * @param array{labels?: array<int, string>, data?: array<int, int|string|float>} $trend
     *
     * @return array<string, int>
     */
    private static function trendMap(array $trend): array
    {
        $labels = $trend['labels'] ?? [];
        $data = $trend['data'] ?? [];

        $map = [];

        foreach ($labels as $index => $label) {
            $map[$label] = self::asInt($data[$index] ?? 0);
        }

        return $map;
    }

    private static function asInt(int|string|float $value): int
    {
        return (int) round((float) $value);
    }

    /**
     * @return array<int, string>
     */
    private static function recentMonthLabels(int $months): array
    {
        $months = max(1, $months);
        $now = self::now()->copy()->startOfMonth();
        $start = $now->copy()->subMonths($months - 1);

        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->isoFormat('MMMM');
        }

        return $labels;
    }

    private static function now(): Carbon
    {
        return \Illuminate\Support\Facades\Date::now();
    }
}
