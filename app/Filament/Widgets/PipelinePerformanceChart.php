<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Support\Metrics\EasyMetrics;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

final class PipelinePerformanceChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pipelinePerformanceChart';

    protected static ?int $contentHeight = 320;

    protected function getHeading(): ?string
    {
        return __('app.charts.pipeline_momentum');
    }

    public static function canView(): bool
    {
        return Filament::auth()?->check() ?? false;
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
                    'name' => __('app.labels.leads'),
                    'data' => $series['leads'],
                ],
                [
                    'name' => __('app.labels.opportunities'),
                    'data' => $series['opportunities'],
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
            'legend' => [
                'position' => 'top',
                'fontFamily' => 'inherit',
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 0.5,
                    'opacityFrom' => 0.4,
                    'opacityTo' => 0.15,
                ],
            ],
            'grid' => [
                'strokeDashArray' => 4,
            ],
            'colors' => ['#6366f1', '#22c55e'],
        ];
    }

    /**
     * @return array{labels: array<int, string>, leads: array<int, int>, opportunities: array<int, int>}
     */
    private function getSeries(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            600,
            function (): array {
                $leadTrend = $this->trendForModel(Lead::class);
                $opportunityTrend = $this->trendForModel(Opportunity::class);

                return [
                    'labels' => $leadTrend['labels'],
                    'leads' => $leadTrend['data'],
                    'opportunities' => $opportunityTrend['data'],
                ];
            },
        );
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function trendForModel(string $modelClass): array
    {
        return EasyMetrics::weeklyCounts(
            $this->scopedQuery($modelClass),
            $this->qualifiedDateColumn($modelClass),
            8,
        );
    }

    private function scopedQuery(string $modelClass): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();
        $model = resolve($modelClass);
        $tenant = Filament::getTenant();
        $table = $model->getTable();

        if ($tenant && Schema::connection($model->getConnectionName())->hasColumn($table, 'team_id')) {
            $query->where($model->qualifyColumn('team_id'), $tenant->getKey());
        }

        return $query;
    }

    private function qualifiedDateColumn(string $modelClass): string
    {
        return resolve($modelClass)->qualifyColumn('created_at');
    }

    private function cacheKey(): string
    {
        $tenant = Filament::getTenant()?->getKey() ?? 'public';

        return "apex:pipeline:{$tenant}";
    }
}
