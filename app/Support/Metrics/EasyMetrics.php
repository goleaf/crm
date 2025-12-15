<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use Illuminate\Database\Eloquent\Builder;
use SaKanjo\EasyMetrics\Enums\Range;
use SaKanjo\EasyMetrics\Metrics\Doughnut;
use SaKanjo\EasyMetrics\Metrics\Trend;

final class EasyMetrics
{
    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function weeklyCounts(string|Builder $query, string $dateColumn, int $weeks): array
    {
        [$labels, $data] = Trend::make($query)
            ->ranges([$weeks])
            ->range($weeks)
            ->dateColumn($dateColumn)
            ->countByWeeks();

        return [
            'labels' => array_map(
                self::formatWeekLabel(...),
                $labels,
            ),
            'data' => self::mapToIntegers($data),
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function dailyCounts(string|Builder $query, string $dateColumn, int $days): array
    {
        [$labels, $data] = Trend::make($query)
            ->ranges([$days])
            ->range($days)
            ->dateColumn($dateColumn)
            ->countByDays();

        return [
            'labels' => array_map(
                self::formatDayLabel(...),
                $labels,
            ),
            'data' => self::mapToIntegers($data),
        ];
    }

    /**
     * @param array<int, string>|string|null $options
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function doughnutCounts(string|Builder $query, string $groupBy, array|string|null $options = null): array
    {
        $metric = Doughnut::make($query)
            ->ranges([Range::ALL])
            ->range(Range::ALL)
            ->count($groupBy);

        if ($options !== null) {
            $metric->options($options);
        }

        [$labels, $data] = $metric;

        $labels = array_values($labels);
        $data = self::mapToIntegers($data);

        if ($labels === [] || $data === []) {
            return [
                'labels' => [__('app.charts.unknown')],
                'data' => [0],
            ];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private static function formatWeekLabel(string $label): string
    {
        [$year, $week] = array_pad(explode('-', $label, 2), 2, null);

        if (! $year || ! $week) {
            return $label;
        }

        try {
            return \Illuminate\Support\Facades\Date::now()
                ->setISODate((int) $year, (int) $week)
                ->isoFormat('MMM D');
        } catch (\Throwable) {
            return $label;
        }
    }

    private static function formatDayLabel(string $label): string
    {
        try {
            return \Illuminate\Support\Facades\Date::parse($label)->isoFormat('MMM D');
        } catch (\Throwable) {
            return $label;
        }
    }

    /**
     * @param array<int, mixed> $data
     *
     * @return array<int, int>
     */
    private static function mapToIntegers(array $data): array
    {
        return array_map(
            static fn (mixed $value): int => (int) round((float) $value),
            $data,
        );
    }
}
