<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Note;
use App\Support\Metrics\EasyMetrics;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

final class NotesActivityChart extends ApexChartWidget
{
    protected static ?string $chartId = 'notesActivityChart';

    protected static ?int $contentHeight = 300;

    private int $days = 30;

    protected function getHeading(): ?string
    {
        return __('app.charts.notes_activity');
    }

    protected function getSubheading(): ?string
    {
        return __('app.charts.notes_activity_subheading');
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
                    'name' => __('app.labels.notes'),
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
                    'opacityFrom' => 0.35,
                    'opacityTo' => 0.1,
                ],
            ],
            'grid' => [
                'strokeDashArray' => 4,
            ],
            'colors' => ['#3b82f6'],
            'legend' => [
                'show' => false,
            ],
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function getSeries(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            600,
            fn (): array => EasyMetrics::dailyCounts(
                $this->baseQuery(),
                $this->qualifiedDateColumn(),
                $this->days,
            ),
        );
    }

    private function baseQuery(): Builder
    {
        $query = Note::query();
        $model = new Note;
        $tenant = Filament::getTenant();
        $table = $model->getTable();

        if ($tenant && Schema::connection($model->getConnectionName())->hasColumn($table, 'team_id')) {
            $query->where($model->qualifyColumn('team_id'), $tenant->getKey());
        }

        return $query;
    }

    private function qualifiedDateColumn(): string
    {
        return new Note()->qualifyColumn('created_at');
    }

    private function cacheKey(): string
    {
        $tenant = Filament::getTenant()?->getKey() ?? 'public';

        return "apex:notes_activity:{$tenant}:{$this->days}";
    }
}
