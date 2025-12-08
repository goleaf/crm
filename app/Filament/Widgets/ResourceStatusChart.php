<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\Metrics\EasyMetrics;
use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

final class ResourceStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'resourceStatusChart';

    protected static ?int $contentHeight = 320;

    public string $resourceClass;

    public string $statusColumn = 'status';

    public ?int $cacheSeconds = 600;

    protected function getHeading(): ?string
    {
        return __('app.charts.status_breakdown', [
            'label' => $this->getResourceLabel(),
        ]);
    }

    protected function getChartId(): string
    {
        return 'resource_status_'.Str::slug($this->getResourceLabel().'_'.$this->statusColumn);
    }

    protected function getOptions(): array
    {
        $data = $this->getStatusData();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => self::$contentHeight,
            ],
            'labels' => array_keys($data),
            'series' => array_values($data),
            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontFamily' => 'inherit',
                ],
            ],
            'colors' => $this->getPalette(count($data)),
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                    ],
                ],
            ],
            'stroke' => [
                'width' => 0,
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getStatusData(): array
    {
        return Cache::remember(
            $this->getCacheKey('status'),
            $this->cacheSeconds ?? 600,
            function (): array {
                $result = EasyMetrics::doughnutCounts(
                    $this->baseQuery(),
                    $this->statusColumn,
                    $this->statusOptions(),
                );

                $labels = [];

                foreach ($result['labels'] as $index => $label) {
                    $labels[$this->formatLabel($label)] = $result['data'][$index] ?? 0;
                }

                if ($labels === []) {
                    return [
                        __('app.charts.unknown') => 0,
                    ];
                }

                ksort($labels);

                return $labels;
            }
        );
    }

    /**
     * @return array<int, string>|null
     */
    private function statusOptions(): ?array
    {
        $enumClass = $this->enumCast();

        if ($enumClass === null) {
            return null;
        }

        return array_map(
            static fn (BackedEnum $case): string|int => $case->value,
            $enumClass::cases()
        );
    }

    private function formatLabel(mixed $state): string
    {
        if ($enumClass = $this->enumCast()) {
            $enum = $state instanceof BackedEnum ? $state : $enumClass::tryFrom($state);

            if ($enum instanceof BackedEnum) {
                if (method_exists($enum, 'label')) {
                    return (string) $enum->label();
                }

                if (method_exists($enum, 'getLabel')) {
                    return (string) $enum->getLabel();
                }

                return $enum->name;
            }
        }

        if ($state === null || $state === '') {
            return __('app.charts.unknown');
        }

        return Str::headline((string) $state);
    }

    private function enumCast(): ?string
    {
        $modelClass = $this->resourceClass::getModel();
        $casts = resolve($modelClass)->getCasts();
        $cast = $casts[$this->statusColumn] ?? null;

        if (! is_string($cast)) {
            return null;
        }

        return is_subclass_of($cast, BackedEnum::class) ? $cast : null;
    }

    private function baseQuery(): Builder
    {
        if (isset($this->resourceClass) && method_exists($this->resourceClass, 'getEloquentQuery')) {
            return $this->resourceClass::getEloquentQuery();
        }

        $modelClass = $this->resourceClass::getModel();

        return $modelClass::query();
    }

    private function qualifiedStatusColumn(): string
    {
        $modelClass = $this->resourceClass::getModel();

        return resolve($modelClass)->qualifyColumn($this->statusColumn);
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
            $this->statusColumn,
            $tenantId,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function getPalette(int $count): array
    {
        $colors = [
            '#6366f1',
            '#22c55e',
            '#f59e0b',
            '#ef4444',
            '#14b8a6',
            '#8b5cf6',
            '#0ea5e9',
            '#f97316',
            '#10b981',
        ];

        if ($count <= count($colors)) {
            return array_slice($colors, 0, $count);
        }

        return $colors;
    }
}
