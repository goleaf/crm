<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\Metrics\EasyMetrics;
use Filament\Facades\Filament;
use Filament\Widgets\LineChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reusable Chart.js trend widget powered by Laravel Easy Metrics.
 *
 * Child classes must set $resource to a Filament resource class so the widget
 * can scope queries and labels automatically.
 */
abstract class ChartJsTrendWidget extends LineChartWidget
{
    /**
     * @var class-string
     */
    protected static string $resource;

    protected static string $dateColumn = 'created_at';

    protected static int $weeks = 8;

    protected static ?int $cacheSeconds = 600;

    public function getHeading(): ?string
    {
        return __('app.charts.records_trend', [
            'label' => $this->getResourceLabel(),
            'weeks' => static::$weeks,
        ]);
    }

    protected function getData(): array
    {
        $series = Cache::remember(
            $this->cacheKey(),
            static::$cacheSeconds ?? 600,
            fn (): array => EasyMetrics::weeklyCounts(
                $this->baseQuery(),
                $this->qualifiedDateColumn(),
                static::$weeks,
            ),
        );

        return [
            'datasets' => [
                [
                    'label' => __('app.charts.new_records', [
                        'label' => $this->getResourceLabel(),
                    ]),
                    'data' => $series['data'],
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.16)',
                    'tension' => 0.35,
                    'fill' => 'start',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function cacheKey(): string
    {
        $tenantId = Filament::getTenant()?->getKey() ?? 'public';

        return implode(':', [
            'chartjs',
            Str::slug(static::$resource, '-'),
            static::$dateColumn,
            static::$weeks,
            $tenantId,
        ]);
    }

    protected function baseQuery(): Builder
    {
        if (method_exists(static::$resource, 'getEloquentQuery')) {
            /** @var class-string $resourceModel */
            $resourceModel = static::$resource::getModel();

            return static::$resource::getEloquentQuery()
                ->select([new $resourceModel()->qualifyColumn(static::$dateColumn)]);
        }

        $modelClass = static::$resource::getModel();

        return $modelClass::query()->select([new $modelClass()->qualifyColumn(static::$dateColumn)]);
    }

    protected function qualifiedDateColumn(): string
    {
        $modelClass = static::$resource::getModel();

        return new $modelClass()->qualifyColumn(static::$dateColumn);
    }

    protected function getResourceLabel(): string
    {
        if (method_exists(static::$resource, 'getPluralModelLabel')) {
            return (string) static::$resource::getPluralModelLabel();
        }

        return __('app.labels.records');
    }
}
