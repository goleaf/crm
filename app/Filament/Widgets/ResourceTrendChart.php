<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\Metrics\EasyMetrics;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

final class ResourceTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'resourceTrendChart';

    protected static ?int $contentHeight = 320;

    public string $resourceClass;

    public string $dateColumn = 'created_at';

    public int $weeks = 8;

    public ?int $cacheSeconds = 600;

    protected function getHeading(): ?string
    {
        return __('app.charts.records_trend', [
            'label' => $this->getResourceLabel(),
            'weeks' => $this->weeks,
        ]);
    }

    protected function getChartId(): string
    {
        return 'resource_trend_'.Str::slug($this->getResourceLabel().'_'.$this->dateColumn);
    }

    protected function getOptions(): array
    {
        $series = $this->getSeries();

        return [
            'chart' => [
                'type' => 'area',
                'height' => self::$contentHeight,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => __('app.charts.new_records', [
                        'label' => $this->getResourceLabel(),
                    ]),
                    'data' => $series['data'],
                ],
            ],
            'xaxis' => [
                'categories' => $series['labels'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 0.5,
                    'opacityFrom' => 0.4,
                    'opacityTo' => 0.1,
                ],
            ],
            'grid' => [
                'strokeDashArray' => 4,
            ],
            'colors' => ['#6366f1'],
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function getSeries(): array
    {
        return Cache::remember(
            $this->getCacheKey('trend'),
            $this->cacheSeconds ?? 600,
            fn (): array => EasyMetrics::weeklyCounts(
                $this->baseQuery(),
                $this->qualifiedDateColumn(),
                $this->weeks,
            ),
        );
    }

    private function baseQuery(): Builder
    {
        if (isset($this->resourceClass) && method_exists($this->resourceClass, 'getEloquentQuery')) {
            return $this->resourceClass::getEloquentQuery();
        }

        $modelClass = $this->resourceClass::getModel();

        return $modelClass::query();
    }

    private function qualifiedDateColumn(): string
    {
        $modelClass = $this->resourceClass::getModel();

        return resolve($modelClass)->qualifyColumn($this->dateColumn);
    }

    private function getResourceLabel(): string
    {
        if (isset($this->resourceClass) && class_exists($this->resourceClass) && method_exists($this->resourceClass, 'getPluralModelLabel')) {
            return (string) $this->resourceClass::getPluralModelLabel();
        }

        return __('app.labels.records');
    }

    private function getCacheKey(string $suffix): string
    {
        $tenantId = Filament::getTenant()?->getKey() ?? 'public';

        return implode(':', [
            'apex',
            $suffix,
            Str::slug($this->resourceClass, '-'),
            $this->dateColumn,
            $this->weeks,
            $tenantId,
        ]);
    }
}
